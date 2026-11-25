<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\Notifications\Contracts\EmailProviderInterface;
use App\Modules\Notifications\Contracts\SmsGatewayInterface;
use App\Modules\Notifications\Providers\Email\SmtpEmailProvider;
use App\Modules\Notifications\Providers\Sms\GenericHttpSmsGateway;
use App\Policies\AuditLogPolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
use App\Support\Audit\Listeners\SecurityEventSubscriber;
use App\Support\Audit\Models\AuditLog;
use App\Support\Shared\Models\SystemSetting;
use App\Support\Shared\SystemSettingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsGatewayInterface::class, GenericHttpSmsGateway::class);
        $this->app->bind(EmailProviderInterface::class, SmtpEmailProvider::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Password::defaults(function (): Password {
            $rule = Password::min(10)->letters()->mixedCase()->numbers();

            return $this->app->isProduction()
                ? $rule->symbols()->uncompromised()
                : $rule;
        });

        // Explicit policy mappings. Note: Super Administrator intentionally has NO Gate::before
        // wildcard bypass so financial controls and maker-checker rules are never circumvented (Spec 4.1 & 4.3).
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(SystemSetting::class, SystemSettingPolicy::class);

        Event::subscribe(SecurityEventSubscriber::class);

        View::composer('*', static function ($view): void {
            $view->with('pycBranding', app(SystemSettingService::class)->branding());
        });
    }
}
