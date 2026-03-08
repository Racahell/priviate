<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; padding: 20px; font-size: 12px; }
        .head { text-align: center; margin-bottom: 10px; }
        .logo { height: 40px; margin-bottom: 4px; object-fit: contain; }
        h1 { margin: 0 0 2px; font-size: 18px; letter-spacing: 0.02em; text-transform: uppercase; }
        .subtitle { margin: 0; font-size: 13px; font-weight: 700; text-transform: uppercase; }
        p { margin: 2px 0; font-size: 12px; color: #111; }
        .section-title { margin-top: 12px; padding: 4px 6px; font-weight: 700; text-transform: uppercase; border: 1px solid #444; background: #ececec; }
        table { width: 100%; border-collapse: collapse; margin-top: 0; }
        th, td { border: 1px solid #777; padding: 6px 8px; font-size: 11px; text-align: left; }
        th { background: #efefef; font-weight: 700; text-transform: uppercase; }
        td.num, th.num { text-align: right; }
        tr.total-row td { font-weight: 700; background: #f2f2f2; }
        .meta { margin-top: 2px; }
        @media print {
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="head">
        @if(!empty($logoUrl))
            <img src="{{ asset($logoUrl) }}" alt="Logo" class="logo">
        @endif
        <h1>{{ $siteName }}</h1>
        <p class="subtitle">Laporan Keuangan</p>
        <p class="meta">Periode: {{ $from ?: '-' }} s.d {{ $to ?: '-' }} ({{ strtoupper($period) }})</p>
        <p class="meta">Dicetak: {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>

    <div class="section-title">A. Laporan Laba Rugi</div>
    <table>
        <thead>
            <tr>
                <th>Pos</th>
                <th class="num">Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($dataset['incomeStatement'] ?? []) as $line)
                <tr>
                    <td>{{ $line['label'] }}</td>
                    <td class="num">{{ number_format((float) $line['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">B. Laporan Arus Kas</div>
    <table>
        <thead>
            <tr>
                <th>Pos</th>
                <th class="num">Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($dataset['cashFlowStatement'] ?? []) as $line)
                <tr class="{{ str_contains(strtolower($line['label']), 'kas akhir periode') || str_contains(strtolower($line['label']), 'kas bersih operasional') ? 'total-row' : '' }}">
                    <td>{{ $line['label'] }}</td>
                    <td class="num">{{ number_format((float) $line['amount'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">C. Rincian Beban Operasional</div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kategori</th>
                <th class="num">Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($dataset['expenseBreakdown'] ?? []) as $idx => $line)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $line['category'] }}</td>
                    <td class="num">{{ number_format((float) $line['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Data beban operasional kosong.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2">Total Beban Operasional</td>
                <td class="num">{{ number_format((float) collect($dataset['expenseBreakdown'] ?? [])->sum('total'), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if(!empty($autoPrint))
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
