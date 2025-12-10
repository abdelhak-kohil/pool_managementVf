<table>
    <thead>
        <tr>
            <th colspan="6" style="text-align: center; font-weight: bold; font-size: 16px;">
                Rapport Global des Coachs - {{ \Carbon\Carbon::createFromDate(null, $month, 1)->locale('fr')->monthName }} {{ $year }}
            </th>
        </tr>
        <tr></tr>
        <tr>
            <th style="font-weight: bold; background-color: #f3f4f6;">Coach</th>
            <th style="font-weight: bold; background-color: #f3f4f6;">Type Salaire</th>
            <th style="font-weight: bold; background-color: #f3f4f6;">Sessions</th>
            <th style="font-weight: bold; background-color: #f3f4f6;">Heures (Est.)</th>
            <th style="font-weight: bold; background-color: #f3f4f6;">Détail Calcul</th>
            <th style="font-weight: bold; background-color: #f3f4f6;">Salaire Estimé</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reports as $report)
        <tr>
            <td>{{ $report['coach_name'] }}</td>
            <td>
                @if($report['salary_type'] === 'fixed') Fixe
                @elseif($report['salary_type'] === 'per_hour') Par Heure
                @else Par Séance
                @endif
            </td>
            <td>{{ $report['sessions_count'] }}</td>
            <td>{{ $report['total_hours'] }}</td>
            <td>{{ $report['calculation_details'] }}</td>
            <td>{{ number_format($report['salary'], 2) }} DZD</td>
        </tr>
        @endforeach
        <tr></tr>
        <tr>
            <td colspan="5" style="text-align: right; font-weight: bold;">Total Heures :</td>
            <td style="font-weight: bold;">{{ $grandTotalHours }}</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right; font-weight: bold;">Masse Salariale Estimée :</td>
            <td style="font-weight: bold;">{{ number_format($grandTotalSalary, 2) }} DZD</td>
        </tr>
    </tbody>
</table>
