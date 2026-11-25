<?php

declare(strict_types=1);

namespace App\Modules\Contributions\Services;

use App\Models\User;
use App\Modules\Contributions\Enums\ObligationStatus;
use App\Modules\Contributions\Models\ContributionCampaign;
use App\Modules\Contributions\Models\ContributionType;
use App\Modules\Contributions\Models\MemberObligation;
use App\Modules\Membership\Enums\MemberStatus;
use App\Modules\Membership\Models\Member;
use App\Support\Audit\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ContributionCampaignService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCampaign(array $data, User $actor, bool $generateOneOffObligations = false): ContributionCampaign
    {
        $amountPesewas = (int) $data['amount_pesewas'];
        if ($amountPesewas <= 0) {
            throw new InvalidArgumentException('Campaign amount in pesewas must be greater than zero.');
        }

        $type = isset($data['contribution_type_id'])
            ? ContributionType::query()->findOrFail((int) $data['contribution_type_id'])
            : ContributionType::query()->where('code', (string) ($data['contribution_type_code'] ?? 'special_collection'))->firstOrFail();

        $title = trim((string) $data['title']);
        $code = ! empty($data['code'])
            ? Str::slug((string) $data['code'])
            : Str::slug($title).'-'.strtolower(Str::random(4));

        return DB::transaction(function () use ($type, $code, $title, $amountPesewas, $data, $actor, $generateOneOffObligations): ContributionCampaign {
            $campaign = ContributionCampaign::query()->create([
                'contribution_type_id' => $type->id,
                'code' => $code,
                'title' => $title,
                'description' => $data['description'] ?? null,
                'amount_pesewas' => $amountPesewas,
                'currency' => strtoupper((string) ($data['currency'] ?? 'GHS')),
                'frequency' => (string) ($data['frequency'] ?? 'one_off'),
                'effective_from' => $data['effective_from'] ?? now()->toDateString(),
                'effective_to' => $data['effective_to'] ?? null,
                'due_day_of_month' => max(1, min(28, (int) ($data['due_day_of_month'] ?? 28))),
                'allow_partial_payments' => (bool) ($data['allow_partial_payments'] ?? true),
                'allow_advance_payments' => (bool) ($data['allow_advance_payments'] ?? true),
                'status' => (string) ($data['status'] ?? 'active'),
                'created_by' => $actor->id,
            ]);

            if ($generateOneOffObligations && $campaign->frequency === 'one_off') {
                $this->generateOneOffObligationsForActiveMembers($campaign, $actor);
            }

            $this->auditLogger->log(
                event: 'contributions.campaign_created',
                category: 'finance',
                description: "Created contribution campaign [{$campaign->title}] ({$campaign->amountMoney()->format()}).",
                auditable: $campaign,
                newValues: [
                    'code' => $campaign->code,
                    'amount_pesewas' => $campaign->amount_pesewas,
                    'frequency' => $campaign->frequency,
                    'effective_from' => $campaign->effective_from->toDateString(),
                ],
                actor: $actor,
            );

            return $campaign;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCampaign(ContributionCampaign $campaign, array $data, User $actor): ContributionCampaign
    {
        $allowed = array_intersect_key($data, array_flip([
            'title',
            'description',
            'effective_to',
            'due_day_of_month',
            'allow_partial_payments',
            'allow_advance_payments',
            'status',
        ]));

        $oldValues = $campaign->only(array_keys($allowed));
        $campaign->update($allowed);

        $this->auditLogger->log(
            event: 'contributions.campaign_updated',
            category: 'finance',
            description: "Updated contribution campaign [{$campaign->title}].",
            auditable: $campaign,
            oldValues: $oldValues,
            newValues: $allowed,
            actor: $actor,
        );

        return $campaign->fresh() ?? $campaign;
    }

    /**
     * Resolves the effective active monthly_dues campaign for a given monthly period date (Spec Section 6.1).
     */
    public function resolveEffectiveMonthlyDuesCampaign(CarbonInterface $periodDate): ContributionCampaign
    {
        $dateString = $periodDate->toDateString();

        $campaign = ContributionCampaign::query()
            ->whereHas('type', static fn ($q) => $q->where('code', 'monthly_dues'))
            ->where('frequency', 'monthly')
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $dateString)
            ->where(static function ($q) use ($dateString): void {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $dateString);
            })
            ->orderByDesc('effective_from')
            ->first();

        if ($campaign === null) {
            throw new InvalidArgumentException("No active monthly dues campaign is effective for [{$dateString}].");
        }

        return $campaign;
    }

    public function generateOneOffObligationsForActiveMembers(ContributionCampaign $campaign, ?User $actor = null): int
    {
        $members = Member::query()->where('status', MemberStatus::Active->value)->get();
        $created = 0;

        foreach ($members as $member) {
            $obligation = MemberObligation::query()->firstOrCreate(
                [
                    'member_id' => $member->id,
                    'contribution_campaign_id' => $campaign->id,
                    'period_key' => 'ONE-OFF',
                ],
                [
                    'period_start' => $campaign->effective_from->toDateString(),
                    'period_end' => $campaign->effective_to?->toDateString(),
                    'due_date' => ($campaign->effective_to ?? Carbon::parse($campaign->effective_from)->addDays(30))->toDateString(),
                    'amount_due_pesewas' => $campaign->amount_pesewas,
                    'currency' => $campaign->currency,
                    'status' => ObligationStatus::Unpaid->value,
                    'generated_by' => $actor?->id,
                ]
            );

            if ($obligation->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }
}
