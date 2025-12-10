<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport des Dépenses</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
        .total { text-align: right; font-weight: bold; margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Rapport des Dépenses</h2>
        <p>Généré le {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Titre</th>
                <th>Catégorie</th>
                <th>Méthode</th>
                <th>Ref</th>
                <th style="text-align: right;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $expense)
            <tr>
                <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                <td>{{ $expense->title }}</td>
                <td>{{ $expense->category }}</td>
                <td>{{ ucfirst($expense->payment_method) }}</td>
                <td>{{ $expense->reference }}</td>
                <td style="text-align: right;">{{ number_format($expense->amount, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total Dépenses: {{ number_format($total, 2, ',', ' ') }} DZD
    </div>
</body>
</html>
