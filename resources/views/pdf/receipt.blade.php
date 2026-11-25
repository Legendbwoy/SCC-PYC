<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Official Receipt {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; line-height: 1.5; margin: 24px; }
        .header { border-bottom: 2px solid #0f766e; padding-bottom: 14px; margin-bottom: 20px; }
        .org-title { font-size: 18px; font-weight: bold; color: #0f766e; margin: 0; }
        .sub-title { font-size: 11px; color: #475569; margin: 2px 0 0 0; }
        .badge { display: inline-block; padding: 4px 10px; background: #ecfdf5; color: #065f46; font-weight: bold; border-radius: 4px; font-size: 11px; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; color: #64748b; }
        .amount-box { margin-top: 20px; padding: 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; }
        .total { font-size: 16px; font-weight: bold; color: #0f172a; }
        .footer { margin-top: 32px; font-size: 10px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="org-title">{{ $branding['organization_name'] ?? 'St. Cecilia Catholic Church — Parish Youth Council' }}</p>
        <p class="sub-title">{{ $branding['parish_name'] ?? 'St. Cecilia Catholic Church' }} &bull; Official Contribution Payment Receipt</p>
        <span class="badge">OFFICIAL RECEIPT: {{ $receipt->receipt_number }}</span>
    </div>

    <table>
        <tr>
            <th>Member Name</th>
            <td>{{ $receipt->snapshot['member_name'] ?? $receipt->member?->fullName() }}</td>
            <th>Member Number</th>
            <td>{{ $receipt->snapshot['member_number'] ?? $receipt->member?->member_number }}</td>
        </tr>
        <tr>
            <th>Payment Reference</th>
            <td>{{ $receipt->snapshot['payment_reference'] ?? $receipt->payment?->reference }}</td>
            <th>Payment Method</th>
            <td>{{ $receipt->snapshot['payment_method'] ?? strtoupper((string) $receipt->payment?->method) }}</td>
        </tr>
        <tr>
            <th>Date Issued (Africa/Accra)</th>
            <td>{{ $receipt->issued_at->timezone('Africa/Accra')->format('d M Y, H:i:s') }}</td>
            <th>Ledger Journal Ref</th>
            <td>{{ $receipt->payment?->journalEntry?->journal_number ?? 'POSTED' }}</td>
        </tr>
    </table>

    <h4 style="margin-top: 22px; margin-bottom: 6px;">Allocated Contribution Breakdown</h4>
    <table>
        <thead>
            <tr>
                <th>Contribution / Campaign</th>
                <th>Period</th>
                <th style="text-align: right;">Allocated Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($receipt->snapshot['allocations'] ?? []) as $alloc)
                <tr>
                    <td>{{ $alloc['campaign'] ?? 'Contribution' }}</td>
                    <td>{{ $alloc['period'] ?? '—' }}</td>
                    <td style="text-align: right;">{{ \App\Support\Shared\Money::fromPesewas((int) ($alloc['amount_pesewas'] ?? 0), $receipt->currency)->format() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="amount-box">
        <span class="total">Total Received: {{ $receipt->amountMoney()->format() }}</span>
    </div>

    <div class="footer">
        Computer-generated official receipt issued after verified payment allocation and double-entry ledger posting. No physical signature required.
    </div>
</body>
</html>
