<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingsRequest;
use App\Models\User;
use App\Support\Shared\Models\SystemSetting;
use App\Support\Shared\Money;
use App\Support\Shared\SystemSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $settingService,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', SystemSetting::class);

        $monthlyDues = Money::fromPesewas(
            (int) config('pyc.finance.monthly_dues_pesewas', 1000),
            (string) config('pyc.organization.currency', 'GHS')
        );

        return view('admin.settings.index', [
            'branding' => $this->settingService->branding(),
            'monthlyDues' => $monthlyDues,
        ]);
    }

    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        $mappings = [
            'organization.name' => ['group' => 'organization', 'value' => $validated['organization_name'], 'public' => true],
            'organization.short_name' => ['group' => 'organization', 'value' => $validated['short_name'], 'public' => true],
            'organization.parish_name' => ['group' => 'organization', 'value' => $validated['parish_name'], 'public' => true],
            'branding.tagline' => ['group' => 'branding', 'value' => $validated['tagline'] ?? '', 'public' => true],
            'branding.contact_email' => ['group' => 'branding', 'value' => $validated['contact_email'] ?? '', 'public' => true],
            'branding.contact_phone' => ['group' => 'branding', 'value' => $validated['contact_phone'] ?? '', 'public' => true],
            'branding.address' => ['group' => 'branding', 'value' => $validated['address'] ?? '', 'public' => true],
            'branding.primary_color' => ['group' => 'branding', 'value' => $validated['primary_color'], 'public' => true],
            'branding.accent_color' => ['group' => 'branding', 'value' => $validated['accent_color'], 'public' => true],
        ];

        foreach ($mappings as $key => $meta) {
            $this->settingService->set(
                key: $key,
                value: $meta['value'],
                group: $meta['group'],
                type: 'string',
                isPublic: $meta['public'],
                actor: $actor,
            );
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Organization and branding configuration saved.');
    }
}
