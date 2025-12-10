<?php

namespace App\Exports;

use App\Models\Finance\Payment;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PaymentsExport implements FromView
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = Payment::with(['subscription.member', 'staff'])->orderByDesc('payment_date');

        if (!empty($this->filters['method']) && $this->filters['method'] != 'all') {
            $query->where('payment_method', $this->filters['method']);
        }

        if (!empty($this->filters['from']) && !empty($this->filters['to'])) {
            $query->whereBetween('payment_date', [
                $this->filters['from'] . ' 00:00:00',
                $this->filters['to'] . ' 23:59:59',
            ]);
        }

        return view('exports.payments', [
            'payments' => $query->get(),
            'filters'  => $this->filters,
        ]);
    }
}
