<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement</title>
    <style>
        body { font-size: 14px; color: #333; line-height: 1.4; }
        .header { width: 100%; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px; }
        .company-info { float: left; }
        .receipt-info { float: right; text-align: right; }
        .company-name { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 5px; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; color: #555; }
        .details-box { margin-bottom: 30px; overflow: hidden; }
        .customer-box { float: left; width: 45%; }
        .meta-box { float: right; width: 45%; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background-color: #f8fafc; padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: bold; color: #475569; }
        td { padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .total-section { float: right; width: 40%; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .total-row.final { font-size: 18px; font-weight: bold; color: #2563eb; border-top: 2px solid #eee; margin-top: 8px; padding-top: 12px; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #eee; padding-top: 20px; }
        .status-paid { color: #16a34a; font-weight: bold; text-transform: uppercase; border: 2px solid #16a34a; padding: 5px 10px; border-radius: 4px; display: inline-block; transform: rotate(-5deg); margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">Thamaghra Parc</div>
            <div>Nouvelle ville, Tizi Ouzou, Algeria</div>
            <div>thamaghra_sarl@yahoo.fr | 026 11 73 22</div>
        </div>
        <div class="receipt-info">
            <div class="title">REÇU DE PAIEMENT</div>
            <div>N° {{ $receipt_number }}</div>
            <div>Date : {{ $date }}</div>
            <div class="status-paid">PAYÉ</div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="details-box">
        <div class="customer-box">
            <strong>Facturé à :</strong><br>
            {{ $customer_name }}<br>
            @if($customer_email) {{ $customer_email }}<br> @endif
            @if($customer_address) {{ $customer_address }} @endif
        </div>
        <div class="meta-box">
            <strong>Mode de paiement :</strong> {{ ucfirst($payment_method) }}<br>
            <strong>Reçu par :</strong> {{ $staff_name }}
        </div>
        <div style="clear: both;"></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: center;">Qté</th>
                <th style="text-align: right;">Prix Unit.</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td style="text-align: center;">{{ $item['quantity'] }}</td>
                <td style="text-align: right;">{{ number_format($item['unit_price'], 2) }} €</td>
                <td style="text-align: right;">{{ number_format($item['total'], 2) }} €</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total HT :</span>
            <span>{{ number_format($total, 2) }} DA</span> <!-- Adjust tax logic if needed -->
        </div>
        <div class="total-row">
            <span>TVA (0%) :</span>
            <span>{{ number_format($total - ($total), 2) }} DA</span>
        </div>
        <div class="total-row final">
            <span>Total TTC :</span>
            <span>{{ number_format($total, 2) }} DA</span>
        </div>
    </div>
    
    <div class="footer">
        Merci de votre confiance !<br>
        Thamaghra Parc - SARL au capital de ##.######DA - SIRET ##.######.######.######
    </div>
</body>
</html>
