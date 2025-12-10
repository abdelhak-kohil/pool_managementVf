<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('pool_schema.expenses', function (Blueprint $table) {
            $table->id('expense_id');
            $table->string('title');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('category'); // salary, electricity, pool_products, equipment, maintenance, ads, other
            $table->text('description')->nullable();
            $table->string('payment_method')->default('cash'); // cash, transfer, check
            $table->string('reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('pool_schema.staff', 'staff_id')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
