<?php

declare(strict_types=1);

namespace App\Support\Audit;

use App\Models\User;
use App\Support\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Record an append-only audit log entry.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        string $event,
        string $category,
        string $description,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?User $actor = null,
    ): AuditLog {
        $resolvedActor = $actor ?? (Auth::user() instanceof User ? Auth::user() : null);

        return AuditLog::query()->create([
            'actor_id' => $resolvedActor?->getKey(),
            'event' => $event,
            'category' => $category,
            'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'old_values' => $this->redactSecrets($oldValues),
            'new_values' => $this->redactSecrets($newValues),
            'metadata' => $this->redactSecrets($metadata),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent() ? mb_substr((string) Request::userAgent(), 0, 500) : null,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    private function redactSecrets(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $sensitiveFragments = ['password', 'secret', 'token', 'recovery_code', 'api_key'];
        $sanitized = [];

        foreach ($payload as $key => $value) {
            $lowerKey = strtolower((string) $key);
            $isSensitive = false;

            foreach ($sensitiveFragments as $fragment) {
                if (str_contains($lowerKey, $fragment)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->redactSecrets($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
