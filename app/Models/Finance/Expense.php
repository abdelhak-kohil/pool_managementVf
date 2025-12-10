<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'pool_schema.expenses';
    protected $primaryKey = 'expense_id';

    protected $fillable = [
        'title',
        'amount',
        'expense_date',
        'category',
        'description',
        'payment_method',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'created_by', 'staff_id');
    }}
