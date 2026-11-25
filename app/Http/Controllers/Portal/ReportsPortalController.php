<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Reporting\Services\ReportingService;
use App\Support\Security\PycPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsPortalController extends Controller
{
    public function __construct(
        private readonly ReportingService $reportingService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(PycPermission::ReportsView->value), 403);

        $year = now('Africa/Accra')->year;
        $fromDate = $request->string('from', "{$year}-01-01")->toString();
        $toDate = $request->string('to', "{$year}-12-31")->toString();

        return view('portal.reports.index', [
            'snapshot' => $this->reportingService->executiveSnapshot($fromDate, $toDate),
        ]);
    }

    public function export(Request $request, string $format): Response|StreamedResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        abort_unless($actor->can(PycPermission::ReportsExport->value), 403);

        $validated = $request->validate([
            'report' => ['required', 'string', Rule::in([
                'membership_register',
                'member_arrears',
                'trial_balance',
                'income_expenditure',
                'expenses_register',
            ])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $year = now('Africa/Accra')->year;
        $fromDate = (string) ($validated['from'] ?? "{$year}-01-01");
        $toDate = (string) ($validated['to'] ?? "{$year}-12-31");

        if ($format === 'csv') {
            return $this->reportingService->exportCsv($validated['report'], $fromDate, $toDate, $actor);
        }

        if ($format === 'pdf') {
            return $this->reportingService->exportPdf($validated['report'], $fromDate, $toDate, $actor);
        }

        abort(404);
    }
}
