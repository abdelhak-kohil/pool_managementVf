<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Receipt #{{ $payment->payment_id }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; margin: 20px; color: #333; }
    .receipt-container { border: 1px solid #ccc; border-radius: 8px; padding: 20px; width: 100%; }
    h2 { text-align: center; color: #0a58ca; margin-bottom: 10px; }
    .header { text-align: center; margin-bottom: 20px; }
    .info { margin-bottom: 15px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    td, th { padding: 6px 8px; border: 1px solid #ccc; font-size: 13px; }
    .total { text-align: right; font-weight: bold; font-size: 16px; }
    .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
  </style>
</head>
<body>
  <div class="receipt-container">
    <div class="header">
      <h2>🏊 Pool Subscription Payment Receipt</h2>
      <p><strong>Receipt ID:</strong> #{{ $payment->payment_id }}</p>
      <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d H:i') }}</p>
    </div>

    <div class="info">
      <p><strong>Member:</strong> {{ $payment->subscription->member->first_name }} {{ $payment->subscription->member->last_name }}</p>
      <p><strong>Plan:</strong> {{ $payment->subscription->plan->plan_name }}</p>
      <p><strong>Payment Method:</strong> {{ ucfirst($payment->payment_method) }}</p>
    </div>

    <table>
      <tr>
        <th>Description</th>
        <th>Amount (DZD)</th>
      </tr>
      <tr>
        <td>Subscription Payment</td>
        <td>{{ number_format($payment->amount, 2) }}</td>
      </tr>
    </table>

    <p class="total">Total: {{ number_format($payment->amount, 2) }} DZD</p>

    @if($payment->notes)
      <p><strong>Notes:</strong> {{ $payment->notes }}</p>
    @endif

    <div class="footer">
      <p>Received by: {{ $payment->staff->first_name }} {{ $payment->staff->last_name }}</p>
      <p>Thank you for your payment!</p>
      <p>Pool Management System © {{ date('Y') }}</p>
    </div>
  </div>
</body>
</html>
