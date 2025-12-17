<?php

namespace App\Modules\Finance\DTOs;

use Illuminate\Http\Request;

class ExpenseData
{
    public function __construct(
        public string $title,
        public float $amount,
        public string $expense_date,
        public string $category,
        public string $payment_method,
        public ?string $reference,
        public ?string $description
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->input('title'),
            amount: (float) $request->input('amount'),
            expense_date: $request->input('expense_date'),
            category: $request->input('category'),
            payment_method: $request->input('payment_method'),
            reference: $request->input('reference'),
            description: $request->input('description')
        );
    }
}
