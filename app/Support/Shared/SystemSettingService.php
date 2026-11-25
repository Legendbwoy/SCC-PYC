<?php

declare(strict_types=1);

namespace App\Support\Shared;

use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Shared\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SystemSettingService
{
    private const CACHE_KEY = 'pyc.system_settings.all';

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if (! $this->tableReady()) {
            return [];
        }

        /** @var array<string, mixed> $settings */
        $settings = Cache::rememberForever(self::CACHE_KEY, static function (): array {
            return SystemSetting::query()
                ->pluck('value', 'key')
                ->all();
        });

        return $settings;
    }

    public function set(
        string $key,
        mixed $value,
        string $group = 'general',
        string $type = 'string',
        ?string $description = null,
        bool $isPublic = false,
        ?User $actor = null,
    ): SystemSetting {
        $existing = SystemSetting::query()->where('key', $key)->first();
        $oldValue = $existing?->value;

        $setting = SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $value,
                'type' => $type,
                'description' => $description ?? $existing?->description,
                'is_public' => $isPublic,
                'updated_by' => $actor?->getKey(),
            ]
        );

        Cache::forget(self::CACHE_KEY);

        if ($actor !== null && $oldValue !== $value) {
            $this->auditLogger->log(
                event: 'settings.updated',
                category: 'settings',
                description: "Updated system setting [{$key}].",
                auditable: $setting,
                oldValues: ['value' => $oldValue],
                newValues: ['value' => $value],
                actor: $actor,
            );
        }

        return $setting;
    }

    /**
     * Resolves organization & branding identity with fallback to config/pyc.php.
     *
     * @return array<string, mixed>
     */
    public function branding(): array
    {
        return [
            'organization_name' => (string) $this->get('organization.name', config('pyc.organization.name')),
            'short_name' => (string) $this->get('organization.short_name', config('pyc.organization.short_name')),
            'parish_name' => (string) $this->get('organization.parish_name', config('pyc.organization.parish_name')),
            'tagline' => (string) $this->get('branding.tagline', config('pyc.branding.tagline')),
            'primary_color' => (string) $this->get('branding.primary_color', config('pyc.branding.primary_color')),
            'accent_color' => (string) $this->get('branding.accent_color', config('pyc.branding.accent_color')),
            'contact_email' => (string) $this->get('branding.contact_email', config('pyc.branding.contact_email')),
            'contact_phone' => (string) $this->get('branding.contact_phone', config('pyc.branding.contact_phone')),
            'address' => (string) $this->get('branding.address', config('pyc.branding.address')),
            'currency' => (string) $this->get('organization.currency', config('pyc.organization.currency', 'GHS')),
            'timezone' => (string) $this->get('organization.timezone', config('pyc.organization.timezone', 'Africa/Accra')),
        ];
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('system_settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
