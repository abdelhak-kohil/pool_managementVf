<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Finance\Actions\Payments\CreatePaymentAction;
use App\Modules\Finance\Actions\Payments\UpdatePaymentAction;
use App\Modules\Finance\Actions\Payments\DeletePaymentAction;
use App\Modules\Finance\Actions\Expenses\CreateExpenseAction;
use App\Modules\Finance\Actions\Expenses\UpdateExpenseAction;
use App\Modules\Finance\DTOs\PaymentData;
use App\Modules\Finance\DTOs\ExpenseData;
use App\Models\Finance\Payment;
use App\Models\Finance\Expense;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class FinanceActionTest extends TestCase
{
    use DatabaseTransactions;

    protected $staffId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createPayment = app(CreatePaymentAction::class);
        $this->updatePayment = app(UpdatePaymentAction::class);
        $this->deletePayment = app(DeletePaymentAction::class);
        $this->createExpense = app(CreateExpenseAction::class);
        $this->updateExpense = app(UpdateExpenseAction::class);

        // Ensure a staff exists (Simpler)
        $this->staffId = DB::table('pool_schema.staff')->insertGetId([
            'first_name' => 'Test', 
            'last_name' => 'Staff', 
            'email' => 'test@staff.com',
            'username' => 'teststaff',
            'phone_number' => '1234567890',
            'password_hash' => '$2y$12$K.e.7/s.8/s.8/s.8/s.8/s.8/s.8/s.8/s.8/s.8/s.8', 
            'salary_type' => 'fixed',
            'hourly_rate' => 0,
            'role_id' => null, 
            'is_active' => true
        ], 'staff_id');
    }

    public function test_can_create_and_manage_payment()
    {
        // 1. Setup Dependencies (Plan, Activity, Member, Subscription)
        $memberId = DB::table('pool_schema.members')->insertGetId(['first_name'=>'Fin','last_name'=>'User'], 'member_id');
        // Use minimal fields known to work in AccessActionTest
        $planId = DB::table('pool_schema.plans')->insertGetId([
            'plan_name'=>'Gold Pay', 
            'plan_type'=>'standard',
            'is_active'=>true
        ], 'plan_id'); 
        
        $activityId = DB::table('pool_schema.activities')->insertGetId(['name'=>'AquaPay', 'is_active'=>true], 'activity_id');
        
        $subId = DB::table('pool_schema.subscriptions')->insertGetId([
            'member_id'=>$memberId, 'plan_id'=>$planId, 'activity_id'=>$activityId, 
            'start_date'=>now(), 'end_date'=>now()->addDays(30), 'status'=>'active'
        ], 'subscription_id');

        // 2. Create Payment
        $dto = new PaymentData(
            subscription_id: $subId,
            amount: 100.50,
            payment_method: 'cash',
            notes: 'Initial Payment'
        );
        $payment = $this->createPayment->execute($dto, $this->staffId);

        $this->assertDatabaseHas('pool_schema.payments', [
            'payment_id' => $payment->payment_id,
            'amount' => 100.50,
            'payment_method' => 'cash'
        ]);

        // 3. Update Payment
        $updateDto = new PaymentData(
            subscription_id: $subId,
            amount: 150.00,
            payment_method: 'card',
            notes: 'Updated Amount'
        );
        $this->updatePayment->execute($payment->fresh(), $updateDto, $this->staffId);

        $this->assertDatabaseHas('pool_schema.payments', [
            'payment_id' => $payment->payment_id,
            'amount' => 150.00,
            'payment_method' => 'card'
        ]);

        // 4. Delete Payment
        $this->deletePayment->execute($payment->fresh());
        $this->assertDatabaseMissing('pool_schema.payments', ['payment_id' => $payment->payment_id]);
    }

    public function test_can_create_and_manage_expense()
    {
        // 1. Create Expense
        $dto = new ExpenseData(
            title: 'Pool Chemicals',
            amount: 250.75,
            expense_date: now()->format('Y-m-d'),
            category: 'pool_products',
            payment_method: 'transfer',
            reference: 'REF123',
            description: 'Chlorine supply'
        );

        $expense = $this->createExpense->execute($dto, $this->staffId);

        $this->assertDatabaseHas('pool_schema.expenses', [
            'expense_id' => $expense->expense_id,
            'title' => 'Pool Chemicals',
            'amount' => 250.75
        ]);

        // 2. Update Expense
        $updateDto = new ExpenseData(
            title: 'Pool Chemicals Updated',
            amount: 300.00,
            expense_date: now()->format('Y-m-d'),
            category: 'pool_products',
            payment_method: 'transfer',
            reference: 'REF123-V2',
            description: 'Correction'
        );

        $this->updateExpense->execute($expense->fresh(), $updateDto);

        $this->assertDatabaseHas('pool_schema.expenses', [
            'expense_id' => $expense->expense_id,
            'title' => 'Pool Chemicals Updated',
            'amount' => 300.00
        ]);
    }
}
