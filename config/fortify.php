<?php

declare(strict_types=1);

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'lowercase_usernames' => true,
    'home' => '/dashboard',
    'prefix' => '',
    'domain' => null,
    'middleware' => ['web'],

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],

    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Enabled Authentication Features (Spec Section 4.4 & 5.2)
    |--------------------------------------------------------------------------
    | Public self-registration is intentionally disabled: member accounts are
    | provisioned exclusively via approved Google Form applications + invitations,
    | and officer accounts via authorized user administration.
    */
    'features' => [
        Features::resetPasswords(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],
];
