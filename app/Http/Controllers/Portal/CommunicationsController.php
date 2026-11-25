<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Notifications\Models\NotificationCampaign;
use App\Modules\Notifications\Models\NotificationDelivery;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Services\GsmSegmentEstimator;
use App\Modules\Notifications\Services\NotificationOrchestrator;
use App\Modules\Notifications\Services\NotificationTemplateService;
use App\Support\Audit\AuditLogger;
use App\Support\Security\PycPermission;
use App\Support\Shared\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunicationsController extends Controller
{
    public function __construct(
        private readonly NotificationTemplateService $templateService,
        private readonly NotificationOrchestrator $orchestrator,
        private readonly GsmSegmentEstimator $gsmEstimator,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can(PycPermission::NotificationsView->value), 403);

        $statusCounts = NotificationDelivery::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $channelCounts = NotificationDelivery::query()
            ->selectRaw('channel, count(*) as total')
            ->groupBy('channel')
            ->pluck('total', 'channel')
            ->all();

        $totalCostPesewas = $user->can(PycPermission::NotificationsViewCosts->value)
            ? (int) NotificationDelivery::query()->sum('cost_pesewas')
            : null;

        $recentDeliveries = $user->can(PycPermission::NotificationsViewDeliveryLogs->value)
            ? NotificationDelivery::query()->with('log')->latest('id')->limit(10)->get()
            : collect();

        return view('portal.communications.index', [
            'statusCounts' => $statusCounts,
            'channelCounts' => $channelCounts,
            'totalCost' => $totalCostPesewas !== null ? Money::fromPesewas($totalCostPesewas, 'GHS') : null,
            'templateCount' => NotificationTemplate::query()->where('is_active', true)->count(),
            'recentDeliveries' => $recentDeliveries,
            'smsEndpointConfigured' => trim((string) config('notifications.sms.generic_http.endpoint', '')) !== '',
        ]);
    }

    public function templates(Request $request): View
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsView->value), 403);

        $templates = NotificationTemplate::query()
            ->with(['currentVersion', 'versions'])
            ->orderBy('category')
            ->orderBy('code')
            ->orderBy('channel')
            ->get();

        return view('portal.communications.templates', [
            'templates' => $templates,
            'gsmEstimator' => $this->gsmEstimator,
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::NotificationsManageTemplates->value), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', Rule::in(['membership', 'events', 'finance', 'security', 'general'])],
            'channel' => ['required', 'string', Rule::in(['sms', 'email', 'in_app'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'required_variables_csv' => ['nullable', 'string', 'max:500'],
            'is_transactional' => ['nullable', 'boolean'],
            'change_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $vars = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($validated['required_variables_csv'] ?? ''))
        )));

        $template = $this->templateService->saveTemplateVersion(
            code: $validated['code'],
            name: $validated['name'],
            category: $validated['category'],
            channel: $validated['channel'],
            body: $validated['body'],
            subject: $validated['subject'] ?: null,
            requiredVariables: $vars,
            isTransactional: $request->boolean('is_transactional', true),
            isActive: true,
            changeNotes: $validated['change_notes'] ?: null,
            actor: $actor,
        );

        return redirect()
            ->route('communications.templates')
            ->with('status', "Saved [{$template->code}] ({$template->channel}) version v{$template->currentVersion?->version_number}.");
    }

    public function sendTest(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless(
            $actor->can(PycPermission::NotificationsManageProviders->value) || $actor->can(PycPermission::NotificationsSend->value),
            403
        );

        $validated = $request->validate([
            'channel' => ['required', 'string', Rule::in(['sms', 'email'])],
            'destination' => ['required', 'string', 'max:190'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $delivery = $this->orchestrator->sendAuthorizedTest(
            channel: $validated['channel'],
            destination: $validated['destination'],
            messageBody: $validated['message'],
            subject: $validated['subject'] ?? null,
            actor: $actor,
        );

        return redirect()
            ->route('communications.index')
            ->with('status', "Test {$validated['channel']} notification (#{$delivery->id}) processed with status: {$delivery->status}.");
    }

    public function deliveries(Request $request): View
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsViewDeliveryLogs->value), 403);

        $channel = $request->string('channel')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $query = NotificationDelivery::query()->with(['log', 'templateVersion'])->latest('id');

        if ($channel !== '') {
            $query->where('channel', $channel);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        return view('portal.communications.deliveries', [
            'deliveries' => $query->paginate(20)->withQueryString(),
            'selectedChannel' => $channel,
            'selectedStatus' => $status,
        ]);
    }

    public function retryDelivery(Request $request, NotificationDelivery $delivery): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::NotificationsRetryFailed->value), 403);

        $this->orchestrator->retryFailedDelivery($delivery, $actor);

        return back()->with('status', "Retry queued for delivery #{$delivery->id}.");
    }

    public function campaigns(Request $request): View
    {
        abort_unless($request->user()?->can(PycPermission::NotificationsView->value), 403);

        return view('portal.communications.campaigns', [
            'campaigns' => NotificationCampaign::query()->with('creator')->latest('id')->paginate(15),
        ]);
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::NotificationsSendBulk->value), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', Rule::in(['announcements', 'events'])],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', Rule::in(['sms', 'email'])],
            'subject' => ['nullable', 'string', 'max:255'],
            'sms_body' => ['nullable', 'string', 'max:640'],
            'email_body' => ['nullable', 'string', 'max:10000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        $isScheduled = ! empty($validated['scheduled_at']);
        if ($isScheduled) {
            abort_unless($actor->can(PycPermission::NotificationsSchedule->value), 403);
        }

        $campaign = NotificationCampaign::query()->create([
            'reference' => 'PYC-CMP-'.now()->year.'-'.strtoupper(Str::random(6)),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'channels' => array_values($validated['channels']),
            'subject' => $validated['subject'] ?: $validated['title'],
            'sms_body' => $validated['sms_body'] ?: null,
            'email_body' => $validated['email_body'] ?: null,
            'status' => $isScheduled ? 'scheduled' : 'draft',
            'scheduled_at' => $validated['scheduled_at'] ?: null,
            'created_by' => $actor->id,
        ]);

        if ($isScheduled) {
            $this->auditLogger->log(
                event: 'notifications.campaign_scheduled',
                category: 'notifications',
                description: "Scheduled bulk campaign [{$campaign->reference}].",
                auditable: $campaign,
                actor: $actor,
            );

            return redirect()
                ->route('communications.campaigns')
                ->with('status', "Campaign {$campaign->reference} scheduled.");
        }

        $dispatched = $this->orchestrator->dispatchBulkCampaign($campaign, $actor);

        return redirect()
            ->route('communications.campaigns')
            ->with('status', "Bulk campaign {$dispatched->reference} dispatched to {$dispatched->total_recipients} consenting recipients.");
    }

    public function cancelCampaign(Request $request, NotificationCampaign $campaign): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::NotificationsCancel->value), 403);

        if ($campaign->status === 'completed') {
            return back()->withErrors(['campaign' => 'Completed campaigns cannot be cancelled.']);
        }

        $campaign->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
        ]);

        $this->auditLogger->log(
            event: 'notifications.campaign_cancelled',
            category: 'notifications',
            description: "Cancelled bulk campaign [{$campaign->reference}].",
            auditable: $campaign,
            actor: $actor,
        );

        return back()->with('status', "Campaign {$campaign->reference} cancelled.");
    }
}
