<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Models\User;
use App\Modules\Notifications\Exceptions\TemplateValidationException;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Models\NotificationTemplateVersion;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Manages versioned notification templates and safe placeholder rendering (Spec Section 10.8–10.10).
 */
class NotificationTemplateService
{
    public function __construct(
        private readonly GsmSegmentEstimator $gsmEstimator,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Creates a new template or appends an immutable version to an existing (code, channel) template.
     *
     * @param  list<string>  $requiredVariables
     */
    public function saveTemplateVersion(
        string $code,
        string $name,
        string $category,
        string $channel,
        string $body,
        ?string $subject = null,
        array $requiredVariables = [],
        bool $isTransactional = true,
        bool $isActive = true,
        ?string $changeNotes = null,
        ?User $actor = null,
    ): NotificationTemplate {
        $normalizedVars = array_values(array_unique(array_map('trim', $requiredVariables)));
        $this->assertPlaceholdersMatchDeclaredVariables($subject, $body, $normalizedVars);

        return DB::transaction(function () use (
            $code,
            $name,
            $category,
            $channel,
            $body,
            $subject,
            $normalizedVars,
            $isTransactional,
            $isActive,
            $changeNotes,
            $actor
        ): NotificationTemplate {
            $template = NotificationTemplate::query()
                ->where('code', $code)
                ->where('channel', $channel)
                ->lockForUpdate()
                ->first();

            $nextVersionNumber = 1;

            if ($template === null) {
                $template = NotificationTemplate::query()->create([
                    'code' => $code,
                    'name' => $name,
                    'category' => $category,
                    'channel' => $channel,
                    'is_transactional' => $isTransactional,
                    'is_active' => $isActive,
                    'updated_by' => $actor?->getKey(),
                ]);
            } else {
                $maxVersion = (int) NotificationTemplateVersion::query()
                    ->where('notification_template_id', $template->id)
                    ->max('version_number');
                $nextVersionNumber = $maxVersion + 1;

                $template->update([
                    'name' => $name,
                    'category' => $category,
                    'is_transactional' => $isTransactional,
                    'is_active' => $isActive,
                    'updated_by' => $actor?->getKey(),
                ]);
            }

            $version = NotificationTemplateVersion::query()->create([
                'notification_template_id' => $template->id,
                'version_number' => $nextVersionNumber,
                'subject' => $subject,
                'body' => $body,
                'required_variables' => $normalizedVars,
                'change_notes' => $changeNotes ?? ($nextVersionNumber === 1 ? 'Initial template version' : "Updated to v{$nextVersionNumber}"),
                'created_by' => $actor?->getKey(),
            ]);

            $template->update(['current_version_id' => $version->id]);

            if ($actor !== null) {
                $this->auditLogger->log(
                    event: 'notifications.template_version_saved',
                    category: 'notifications',
                    description: "Saved [{$code}] ({$channel}) template version v{$nextVersionNumber}.",
                    auditable: $template,
                    newValues: [
                        'version_number' => $nextVersionNumber,
                        'channel' => $channel,
                        'is_active' => $isActive,
                    ],
                    actor: $actor,
                );
            }

            return $template->fresh(['currentVersion']) ?? $template;
        });
    }

    /**
     * Strictly validates required & unknown variables and renders subject + body for the target channel.
     * Escapes untrusted variable values in HTML emails (Spec Section 10.10).
     *
     * @param  array<string, scalar|null>  $variables
     * @return array{subject: ?string, body: string, gsm_estimate: ?array{encoding: string, units: int, segments: int, per_segment_limit: int}}
     */
    public function renderVersion(NotificationTemplateVersion $version, string $channel, array $variables): array
    {
        /** @var list<string> $required */
        $required = array_values($version->required_variables ?? []);

        foreach ($required as $requiredKey) {
            if (! array_key_exists($requiredKey, $variables) || trim((string) $variables[$requiredKey]) === '') {
                throw new TemplateValidationException(
                    "Missing required template variable: [{{$requiredKey}}]."
                );
            }
        }

        $unknownSupplied = array_diff(array_keys($variables), $required);
        if ($unknownSupplied !== []) {
            $firstUnknown = reset($unknownSupplied);
            throw new TemplateValidationException(
                "Unknown variable [{$firstUnknown}] is not declared for this template."
            );
        }

        $placeholdersInContent = $this->extractPlaceholders(($version->subject ?? '').' '.$version->body);
        $undeclaredInTemplate = array_diff($placeholdersInContent, $required);
        if ($undeclaredInTemplate !== []) {
            $firstUndeclared = reset($undeclaredInTemplate);
            throw new TemplateValidationException(
                "Template contains undeclared placeholder [{{$firstUndeclared}}]."
            );
        }

        $escapeHtml = $channel === 'email';

        $renderedSubject = $version->subject !== null
            ? $this->substitute($version->subject, $variables, false)
            : null;

        $renderedBody = $this->substitute($version->body, $variables, $escapeHtml);

        return [
            'subject' => $renderedSubject,
            'body' => $renderedBody,
            'gsm_estimate' => $channel === 'sms' ? $this->gsmEstimator->estimate($renderedBody) : null,
        ];
    }

    /**
     * @param  list<string>  $declaredVariables
     */
    private function assertPlaceholdersMatchDeclaredVariables(?string $subject, string $body, array $declaredVariables): void
    {
        $combined = ($subject ?? '').' '.$body;

        // Reject malformed single braces or invalid variable tokens
        $strippedValid = preg_replace('/\{\{\s*[a-zA-Z0-9_]+\s*\}\}/', '', $combined) ?? $combined;
        if (str_contains($strippedValid, '{{') || str_contains($strippedValid, '}}')) {
            throw new TemplateValidationException('Template contains malformed placeholder syntax.');
        }

        $usedPlaceholders = $this->extractPlaceholders($combined);
        $undeclared = array_diff($usedPlaceholders, $declaredVariables);

        if ($undeclared !== []) {
            $first = reset($undeclared);
            throw new TemplateValidationException(
                "Placeholder [{{$first}}] used in template body/subject must be listed in required_variables."
            );
        }
    }

    /**
     * @return list<string>
     */
    private function extractPlaceholders(string $text): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    private function substitute(string $content, array $variables, bool $escapeHtml): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            static function (array $matches) use ($variables, $escapeHtml): string {
                $key = $matches[1];
                $raw = (string) ($variables[$key] ?? '');

                return $escapeHtml ? e($raw) : $raw;
            },
            $content
        );
    }
}
