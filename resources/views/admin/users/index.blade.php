@component('layouts.app', [
    'title' => 'Users & Roles',
    'heading' => 'User & Officer Role Administration',
    'subheading' => 'Provision council officers and manage role assignments. Financial maker-checker rules remain strictly enforced.',
])
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Create Officer / User Form -->
        <div class="pyc-card p-6 h-fit">
            <h2 class="text-base font-extrabold text-slate-900 mb-1">Provision Council Account</h2>
            <p class="text-xs text-slate-500 mb-4">Officers must enable 2FA upon first sign-in.</p>

            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Phone Number (+233...)</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+233240000000"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Assigned PYC Role</label>
                    <select name="role" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white">
                        @foreach($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role') === $role->value)>
                                {{ $role->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Initial Temporary Password</label>
                    <input type="password" name="password" required autocomplete="new-password"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" required autocomplete="new-password"
                           class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="btn-pyc-primary w-full">
                    Create Account
                </button>
            </form>
        </div>

        <!-- Existing Accounts Table -->
        <div class="xl:col-span-2 pyc-card overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-extrabold text-slate-900">Registered Portal Accounts</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">User</th>
                            <th class="px-5 py-3 text-left font-semibold">2FA</th>
                            <th class="px-5 py-3 text-left font-semibold">Role &amp; Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($users as $managedUser)
                            <tr>
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-slate-900">{{ $managedUser->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $managedUser->email }}</p>
                                    @if($managedUser->phone)
                                        <p class="text-xs text-slate-400">{{ $managedUser->phone }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    @if($managedUser->hasConfirmedTwoFactorAuthentication())
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Confirmed</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Unconfirmed</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->value }}" @selected($managedUser->hasRole($role->value))>
                                                    {{ $role->value }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <select name="status" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs bg-white">
                                            <option value="active" @selected($managedUser->status === 'active')>Active</option>
                                            <option value="suspended" @selected($managedUser->status === 'suspended')>Suspended</option>
                                        </select>
                                        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 cursor-pointer">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endcomponent
