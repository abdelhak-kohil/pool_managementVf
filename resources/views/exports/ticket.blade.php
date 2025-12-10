<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Ticket de Caisse</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto; /* 80mm width, auto height */
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 5mm;
            color: #000;
            line-height: 1.2;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        
        .separator {
            border-bottom: 1px dashed #000;
            margin: 5px 0;
            width: 100%;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th { text-align: left; border-bottom: 1px dashed #000; padding: 2px 0; }
        td { padding: 2px 0; vertical-align: top; }
        
        .quantity { width: 15%; }
        .item-name { width: 55%; }
        .price { width: 30%; text-align: right; }
        
        .totals {
            margin-top: 10px;
        }
        .footer {
            margin-top: 15px;
            font-size: 10px;
            text-align: center;
        }
        .barcode {
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="text-center">
        <div class="bold" style="font-size: 16px; margin-bottom: 5px;">CASH RECEIPT</div>
        <div>Adress: Pool Club, Alger</div>
        <div>Tel: 0555-00-00-00</div>
    </div>

    <div class="separator"></div>

    <div>
        <div>Date: {{ $date }}</div>
        <div>Receipt #: {{ $receipt_number }}</div>
        @if($customer_name)
        <div>Client: {{ $customer_name }}</div>
        @endif
        <div>Caissier: {{ $staff_name }}</div>
    </div>

    <div class="separator"></div>

    <table>
        <thead>
            <tr>
                <th class="quantity">Qte</th>
                <th class="item-name">Article</th>
                <th class="price">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td class="quantity">{{ $item['quantity'] }}</td>
                <td class="item-name">{{ $item['name'] }}</td>
                <td class="price">{{ number_format($item['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="separator"></div>

    <div class="totals text-right">
        <table style="width: 100%">
            <tr>
                <td class="text-left bold">Total</td>
                <td class="text-right bold" style="font-size: 14px;">{{ number_format($total, 2) }} DA</td>
            </tr>
            <tr>
                <td class="text-left">Espèces</td>
                <td class="text-right">{{ number_format($total, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">Rendu</td>
                <td class="text-right">0.00</td>
            </tr>
        </table>
    </div>

    <div class="separator"></div>

    <div class="footer">
        <div class="bold" style="font-size: 14px;">THANK YOU</div>
        <div style="margin-top: 5px;">Merci de votre visite!</div>
        
        <div class="barcode">
            <!-- Simple simulated barcode with css/html borders if needed or just text -->
             ||||||||| |||| || ||||||||
             <br>
             {{ $receipt_number }}
        </div>
    </div>
</body>
</html>
