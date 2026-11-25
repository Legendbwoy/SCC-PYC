<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $dataset['title'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11px; margin: 20px; }
        .header { border-bottom: 2px solid #0f766e; padding-bottom: 12px; margin-bottom: 16px; }
        .org { font-size: 16px; font-weight: bold; color: #0f766e; margin: 0; }
        .report-title { font-size: 14px; font-weight: bold; margin: 4px 0; }
        .meta { font-size: 10px; color: #475569; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; }
        .footer { margin-top: 20px; font-size: 9px; color: #64748b; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <p class="org">{{ $branding['organization_name'] ?? 'St. Cecilia Catholic Church — Parish Youth Council' }}</p>
        <p class="report-title">{{ $dataset['title'] }}</p>
        <p class="meta">
            Period: {{ $dataset['period_label'] }} &bull; Currency: GHS &bull; Generated: {{ $generatedAt }} &bull; By: {{ $generatedBy }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($dataset['headers'] as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($dataset['rows'] as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($dataset['headers']) }}" style="text-align: center;">No records for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Derived from authoritative St. Cecilia PYC records &bull; Double-entry ledger reconciled
    </div>
</body>
</html>
