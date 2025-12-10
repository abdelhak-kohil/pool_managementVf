<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport de Présence</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; color: #1e3a8a; }
        .header p { margin: 5px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; color: #333; font-weight: bold; }
        .status-late { color: #dc2626; font-weight: bold; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; }
        .summary { margin-top: 20px; width: 50%; float: right; }
        .summary table td { border: none; padding: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport Mensuel de Présence</h1>
        <p>
            Période : {{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
            @if($staffMember)
                <br>Employé : <strong>{{ $staffMember->full_name }}</strong>
            @endif
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                @if(!$staffMember) <th>Employé</th> @endif
                <th>Entrée</th>
                <th>Sortie</th>
                <th>Total (h)</th>
                <th>Nuit (h)</th>
                <th>Supp. (h)</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr>
                <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d/m/Y') }}</td>
                @if(!$staffMember) <td>{{ $attendance->staff->full_name }}</td> @endif
                <td>{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}</td>
                <td>{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}</td>
                <td>{{ $attendance->working_hours }}</td>
                <td>{{ $attendance->night_hours > 0 ? $attendance->night_hours : '-' }}</td>
                <td>{{ $attendance->overtime_hours > 0 ? $attendance->overtime_hours : '-' }}</td>
                <td>
                    <span class="{{ $attendance->status === 'late' ? 'status-late' : '' }}">
                        {{ ucfirst($attendance->status) }}
                        @if($attendance->delay_minutes > 0)
                            (+{{ $attendance->delay_minutes }} min)
                        @endif
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3>Résumé Global</h3>
        <table>
            <tr>
                <td><strong>Total Heures Travaillées :</strong></td>
                <td>{{ $attendances->sum('working_hours') }} h</td>
            </tr>
            <tr>
                <td><strong>Dont Heures de Nuit :</strong></td>
                <td>{{ $attendances->sum('night_hours') }} h</td>
            </tr>
            <tr>
                <td><strong>Dont Heures Supplémentaires :</strong></td>
                <td>{{ $attendances->sum('overtime_hours') }} h</td>
            </tr>
             <tr>
                <td><strong>Total Retards :</strong></td>
                <td>{{ $attendances->where('status', 'late')->count() }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Généré le {{ now()->format('d/m/Y H:i') }} | Pool Management System
    </div>
</body>
</html>
