<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport de Présence</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
        .status-granted { color: green; font-weight: bold; }
        .status-denied { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Rapport de Présence</h2>
        <p>Période : {{ $from }} au {{ $to }}</p>
    </div>

    <!-- Stats Summary -->
    <div style="margin-bottom: 30px;">
        <h3>Résumé des Statistiques</h3>
        <p><strong>Total Visites :</strong> {{ $totalVisits }}</p>

        <table style="width: 100%; margin-bottom: 20px;">
            <tr>
                <td style="vertical-align: top; width: 33%;">
                    <h4>Jours les plus Actifs</h4>
                    <ul>
                        @foreach($activeDays as $day)
                            <li>{{ trim($day->day_name) }} : {{ $day->count }}</li>
                        @endforeach
                    </ul>
                </td>
                <td style="vertical-align: top; width: 33%;">
                    <h4>Par Activité</h4>
                    <ul>
                        @foreach($activities as $activity)
                            <li>{{ $activity->name }} : {{ $activity->count }}</li>
                        @endforeach
                    </ul>
                </td>
                <td style="vertical-align: top; width: 33%;">
                    <h4>Top 5 Membres</h4>
                    <ol>
                        @foreach($topMembers->take(5) as $member)
                            <li>{{ $member->first_name }} {{ $member->last_name }} ({{ $member->visit_count }})</li>
                        @endforeach
                    </ol>
                </td>
            </tr>
        </table>
    </div>

    <h3>Détail des Passages</h3>
    <table>
        <thead>
            <tr>
                <th>Date / Heure</th>
                <th>Membre</th>
                <th>Badge UID</th>
                <th>Statut</th>
                <th>Raison (si refusé)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ \Carbon\Carbon::parse($log->access_time)->format('d/m/Y H:i:s') }}</td>
                <td>{{ $log->first_name }} {{ $log->last_name }}</td>
                <td>{{ $log->badge_uid }}</td>
                <td>
                    <span class="{{ $log->access_decision === 'granted' ? 'status-granted' : 'status-denied' }}">
                        {{ $log->access_decision === 'granted' ? 'Autorisé' : 'Refusé' }}
                    </span>
                </td>
                <td>{{ $log->denial_reason }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
