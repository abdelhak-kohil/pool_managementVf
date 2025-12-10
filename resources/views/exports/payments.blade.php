<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
    th { background-color: #f3f4f6; }
    h2 { text-align: center; color: #1e3a8a; margin-bottom: 10px; }
  </style>
</head>
<body>
  <h2>Rapport des Paiements - Piscine</h2>
  <p><strong>Filtre:</strong>
    @if(!empty($filters['from'])) du {{ $filters['from'] }} au {{ $filters['to'] }} @endif
    @if(!empty($filters['method']) && $filters['method'] != 'all') — Méthode: {{ ucfirst($filters['method']) }} @endif
  </p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Membre</th>
        <th>Montant (DZD)</th>
        <th>Méthode</th>
        <th>Date</th>
        <th>Personnel</th>
      </tr>
    </thead>
    <tbody>
      @foreach($payments as $i => $p)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $p->subscription->member->first_name }} {{ $p->subscription->member->last_name }}</td>
          <td>{{ number_format($p->amount, 2) }}</td>
          <td>{{ ucfirst($p->payment_method) }}</td>
          <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('d/m/Y H:i') }}</td>
          <td>{{ $p->staff->first_name ?? 'N/A' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <p style="margin-top:20px;"><strong>Total:</strong> {{ number_format($payments->sum('amount'), 2) }} DZD</p>
</body>
</html>
