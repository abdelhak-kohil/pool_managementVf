<?php

namespace App\Modules\Finance\Actions\Expenses;

use App\Modules\Finance\DTOs\ExpenseData;
use App\Models\Finance\Expense;

class UpdateExpenseAction
{
    public function execute(Expense $expense, ExpenseData $data): void
    {
        $expense->update([
            'title' => $data->title,
            'amount' => $data->amount,
            'expense_date' => $data->expense_date,
            'category' => $data->category,
            'payment_method' => $data->payment_method,
            'reference' => $data->reference,
            'description' => $data->description,
        ]);
    }
}
