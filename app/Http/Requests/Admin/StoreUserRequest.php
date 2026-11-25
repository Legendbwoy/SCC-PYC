<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\Security\PycRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $roleValues = array_map(static fn (PycRole $role): string => $role->value, PycRole::cases());

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\s\-]{9,20}$/'],
            'role' => ['required', 'string', Rule::in($roleValues)],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
