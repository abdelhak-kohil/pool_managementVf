<?php

namespace App\Modules\Finance\Actions\Payments;

use App\Models\Finance\Payment;

class DeletePaymentAction
{
    public function execute(Payment $payment): void
    {
        $payment->delete();
    }
}
