<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::loginView(static fn () => view('auth.login'));
        Fortify::twoFactorChallengeView(static fn () => view('auth.two-factor-challenge'));
        Fortify::requestPasswordResetLinkView(static fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(static fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(static fn () => view('auth.confirm-password'));

        Fortify::authenticateUsing(static function (Request $request): ?User {
            $email = Str::lower((string) $request->input('email'));
            /** @var User|null $user */
            $user = User::query()->where('email', $email)->first();

            if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            if (! $user->isActive()) {
                throw ValidationException::withMessages([
                    Fortify::username() => ['This account is currently inactive or suspended. Please contact the PYC administrator.'],
                ]);
            }

            return $user;
        });

        RateLimiter::for('login', static function (Request $request): Limit {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', static function (Request $request): Limit {
            return Limit::perMinute(5)->by((string) ($request->session()->get('login.id') ?: $request->ip()));
        });

        RateLimiter::for('api', static function (Request $request): Limit {
            return Limit::perMinute(60)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });
    }
}
