<?php

declare(strict_types=1);

namespace App\Support\Security\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

final class MakerCheckerViolationException extends AuthorizationException {}
