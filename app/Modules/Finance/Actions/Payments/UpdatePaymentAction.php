<?php

namespace App\Modules\Finance\Actions\Payments;

use App\Modules\Finance\DTOs\PaymentData;
use App\Models\Finance\Payment;

class UpdatePaymentAction
{
    public function execute(Payment $payment, PaymentData $data, int $staffId): void
    {
        $payment->update([
            'subscription_id'     => $data->subscription_id,
            'amount'              => $data->amount,
            'payment_method'      => $data->payment_method,
            'notes'               => $data->notes,
            // Should we update 'received_by' on edit? Usually yes if the editor takes responsibility.
            'received_by_staff_id'=> $staffId, 
            'payment_date'        => now(), // Update date to now on edit? Or keep original? Controller updated it to now(), so I follow that.
        ]);
    }
}
