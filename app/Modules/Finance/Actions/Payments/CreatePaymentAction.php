<?php

namespace App\Modules\Finance\Actions\Payments;

use App\Modules\Finance\DTOs\PaymentData;
use App\Models\Finance\Payment;
use Illuminate\Support\Facades\DB;

class CreatePaymentAction
{
    // Returns Payment model or ID. Returning Model is better if Ajax needs multiple fields.
    public function execute(PaymentData $data, int $staffId): Payment
    {
        // Using Eloquent Create which returns the model
        return Payment::create([
            'subscription_id'     => $data->subscription_id,
            'amount'              => $data->amount,
            'payment_method'      => $data->payment_method,
            'received_by_staff_id'=> $staffId,
            'notes'               => $data->notes,
            'payment_date'        => $data->payment_date ?? now(),
        ]);
    }
}
