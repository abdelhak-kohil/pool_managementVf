<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Global Coachs</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #2563eb; }
        .subtitle { font-size: 14px; color: #666; margin-top: 5px; }
        
        table { w-full; border-collapse: collapse; width: 100%; font-size: 12px; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { bg-color: #f8f9fa; font-weight: bold; }
        
        .total-box { text-align: right; margin-top: 30px; font-size: 18px; font-weight: bold; border-top: 2px solid #333; padding-top: 10px; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #999; }
        
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-fixed { background-color: #e0f2fe; color: #0369a1; }
        .badge-hour { background-color: #dcfce7; color: #15803d; }
        .badge-session { background-color: #f3e8ff; color: #7e22ce; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Rapport Global des Coachs</div>
        <div class="subtitle">
            Période : {{ \Carbon\Carbon::createFromDate(null, $month, 1)->locale('fr')->monthName }} {{ $year }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Coach</th>
                <th>Type Salaire</th>
                <th>Sessions</th>
                <th>Heures (Est.)</th>
                <th>Détail Calcul</th>
                <th>Salaire Estimé</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reports as $report)
            <tr>
                <td>{{ $report['coach_name'] }}</td>
                <td>
                    @if($report['salary_type'] === 'fixed')
                        <span class="badge badge-fixed">Fixe</span>
                    @elseif($report['salary_type'] === 'per_hour')
                        <span class="badge badge-hour">Par Heure</span>
                    @else
                        <span class="badge badge-session">Par Séance</span>
                    @endif
                </td>
                <td>{{ $report['sessions_count'] }}</td>
                <td>{{ $report['total_hours'] }}h</td>
                <td style="font-size: 10px; color: #666;">{{ $report['calculation_details'] }}</td>
                <td style="font-weight: bold;">{{ number_format($report['salary'], 2) }} DZD</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <div>Total Heures : {{ $grandTotalHours }} h</div>
        <div>Masse Salariale Estimée : {{ number_format($grandTotalSalary, 2) }} DZD</div>
    </div>

    <div class="footer">
        Document généré le {{ $date }}
    </div>
</body>
</html>
