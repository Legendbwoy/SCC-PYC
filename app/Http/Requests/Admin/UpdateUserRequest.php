<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\Security\PycRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $target */
        $target = $this->route('user');

        return $this->user()?->can('update', $target) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $roleValues = array_map(static fn (PycRole $role): string => $role->value, PycRole::cases());

        return [
            'role' => ['required', 'string', Rule::in($roleValues)],
            'status' => ['required', 'string', Rule::in(['active', 'suspended'])],
        ];
    }
}
