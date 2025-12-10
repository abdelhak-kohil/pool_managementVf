<table>
    <thead>
        <tr>
            <th colspan="5" style="text-align: center; font-weight: bold; font-size: 16px;">
                Rapport Mensuel Coach - {{ $coach->full_name }} - {{ \Carbon\Carbon::createFromDate(null, $month, 1)->locale('fr')->monthName }} {{ $year }}
            </th>
        </tr>
        <tr></tr> <!-- Empty row -->
        <tr>
            <th style="font-weight: bold; background-color: #f3f4f6;">Date</th>
            <th style="font-weight: bold; background-color: #f3f4f6;">Jour</th>
            <th style="font-weight: bold; background-color: #f3f4f6;">Horaire</th>
            <th style="font-weight: bold; background-color: #f3f4f6;">Activité</th>
            <th style="font-weight: bold; background-color: #f3f4f6;">Durée (h)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sessions as $session)
        <tr>
            <td>{{ $session['date'] }}</td>
            <td>{{ ucfirst($session['day_name']) }}</td>
            <td>{{ substr($session['start_time'], 0, 5) }} - {{ substr($session['end_time'], 0, 5) }}</td>
            <td>{{ $session['activity'] }}</td>
            <td>{{ $session['duration'] }}</td>
        </tr>
        @endforeach
        <tr></tr>
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">Total Heures :</td>
            <td style="font-weight: bold;">{{ $totalHours }}</td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">Total Sessions :</td>
            <td style="font-weight: bold;">{{ $sessionsCount }}</td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">Salaire Estimé :</td>
            <td style="font-weight: bold;">{{ number_format($salary, 2) }} DZD</td>
        </tr>
        <tr>
            <td colspan="5" style="text-align: right; font-style: italic; color: #666;">
                {{ $calculation_details }}
            </td>
        </tr>
    </tbody>
</table>
