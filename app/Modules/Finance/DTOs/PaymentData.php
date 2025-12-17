<?php

namespace App\Modules\Finance\DTOs;

use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentData
{
    public function __construct(
        public int $subscription_id,
        public float $amount,
        public string $payment_method, // 'cash', 'card', 'transfer'
        public ?string $notes,
        public ?string $payment_date = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            subscription_id: (int) $request->input('subscription_id'),
            amount: (float) $request->input('amount'),
            payment_method: $request->input('payment_method'),
            notes: $request->input('notes'),
            payment_date: now()->toDateTimeString()
        );
    }
}
