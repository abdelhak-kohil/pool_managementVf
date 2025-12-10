<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Qualité Eau</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport de Qualité de l'Eau</h1>
        <p>Généré le {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Bassin</th>
                <th>pH</th>
                <th>Chlore (L/T)</th>
                <th>Temp.</th>
                <th>Technicien</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tests as $test)
            <tr>
                <td>{{ $test->test_date->format('d/m/Y H:i') }}</td>
                <td>{{ $test->pool->name ?? 'N/A' }}</td>
                <td>{{ $test->ph }}</td>
                <td>{{ $test->chlorine_free }} / {{ $test->chlorine_total }}</td>
                <td>{{ $test->temperature }}°C</td>
                <td>{{ $test->technician->first_name ?? '' }} {{ $test->technician->last_name ?? '' }}</td>
                <td>{{ $test->comments }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Pool Management System - Rapport Officiel
    </div>
</body>
</html>
