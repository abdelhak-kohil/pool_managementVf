<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Coach</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .title { font-size: 24px; font-weight: bold; color: #2563eb; }
        .subtitle { font-size: 14px; color: #666; margin-top: 5px; }
        
        .section { margin-bottom: 25px; }
        .section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
        
        table { w-full; border-collapse: collapse; width: 100%; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { bg-color: #f8f9fa; font-weight: bold; }
        
        .total-box { text-align: right; margin-top: 20px; font-size: 16px; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Rapport Mensuel Coach</div>
        <div class="subtitle">
            Période : {{ \Carbon\Carbon::createFromDate(null, $month, 1)->locale('fr')->monthName }} {{ $year }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">Informations Coach</div>
        <p><strong>Nom :</strong> {{ $coach->full_name }}</p>
        <p><strong>Spécialité :</strong> {{ $coach->specialty ?? 'N/A' }}</p>
        <p><strong>Type de Salaire :</strong> 
            @if($coach->salary_type === 'per_hour') Par Heure
            @elseif($coach->salary_type === 'per_session') Par Séance
            @else Fixe @endif
        </p>
    </div>

    <div class="section">
        <div class="section-title">Détail des Activités Réalisées</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Jour</th>
                    <th>Horaire</th>
                    <th>Activité</th>
                    <th>Durée</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $session)
                <tr>
                    <td>{{ $session['date'] }}</td>
                    <td>{{ ucfirst($session['day_name']) }}</td>
                    <td>{{ substr($session['start_time'], 0, 5) }} - {{ substr($session['end_time'], 0, 5) }}</td>
                    <td>{{ $session['activity'] }}</td>
                    <td>{{ $session['duration'] }}h</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Récapitulatif & Rémunération Estimée</div>
        <p><strong>Volume Horaire Hebdomadaire :</strong> {{ $totalHours / 4 }} heures</p>
        <p><strong>Volume Horaire Mensuel (Est.) :</strong> {{ $totalHours }} heures</p>
        
        <div class="total-box">
            <div style="font-size: 12px; font-weight: normal; margin-bottom: 5px; color: #666;">
                {{ $calculation_details }}
            </div>
            Salaire Estimé : {{ number_format($salary, 2) }} DZD
        </div>
    </div>

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
