<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycRole;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()->with('roles')->orderBy('name')->paginate(15),
            'roles' => PycRole::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        $created = DB::transaction(function () use ($validated, $actor): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'status' => 'active',
                'must_change_password' => true,
            ]);

            $user->syncRoles([$validated['role']]);

            $this->auditLogger->log(
                event: 'users.created',
                category: 'users',
                description: "Created user [{$user->email}] with role [{$validated['role']}].",
                auditable: $user,
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $validated['role'],
                    'status' => $user->status,
                ],
                actor: $actor,
            );

            return $user;
        });

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Account created for {$created->name} ({$validated['role']}).");
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        if ((int) $actor->getKey() === (int) $user->getKey() && $validated['status'] !== 'active') {
            return back()->withErrors([
                'status' => 'You cannot suspend your own active administrator account.',
            ]);
        }

        if ((int) $actor->getKey() === (int) $user->getKey() && $validated['role'] !== PycRole::SuperAdministrator->value) {
            return back()->withErrors([
                'role' => 'You cannot remove your own Super Administrator role.',
            ]);
        }

        $oldRole = $user->primaryRoleName();
        $oldStatus = $user->status;

        DB::transaction(function () use ($user, $validated, $oldRole, $oldStatus, $actor): void {
            $user->update(['status' => $validated['status']]);
            $user->syncRoles([$validated['role']]);

            $this->auditLogger->log(
                event: 'users.updated',
                category: 'users',
                description: "Updated role/status for user [{$user->email}].",
                auditable: $user,
                oldValues: ['role' => $oldRole, 'status' => $oldStatus],
                newValues: ['role' => $validated['role'], 'status' => $validated['status']],
                actor: $actor,
            );
        });

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Updated {$user->name}'s role and status.");
    }
}
