<?php

namespace App\Modules\Finance\Actions\Expenses;

use App\Modules\Finance\DTOs\ExpenseData;
use App\Models\Finance\Expense;

class CreateExpenseAction
{
    public function execute(ExpenseData $data, int $staffId): Expense
    {
        return Expense::create([
            'title' => $data->title,
            'amount' => $data->amount,
            'expense_date' => $data->expense_date,
            'category' => $data->category,
            'payment_method' => $data->payment_method,
            'reference' => $data->reference,
            'description' => $data->description,
            'created_by' => $staffId
        ]);
    }
}
