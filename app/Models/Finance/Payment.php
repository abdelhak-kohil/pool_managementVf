<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'pool_schema.payments';
    protected $primaryKey = 'payment_id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $timestamps = false;

    protected $casts = [
        'payment_date' => 'datetime',
        'amount'       => 'decimal:2',
        'subscription_id' => 'integer',
        'received_by_staff_id' => 'integer',
    ];

    protected $fillable = [
        'subscription_id',
        'amount',
        'payment_date',
        'payment_method',
        'received_by_staff_id',
        'notes',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'subscription_id');
    }

    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'received_by_staff_id', 'staff_id');

        
    }

     // Add this:
    public function receivedBy()
    {
        // Adjust 'received_by' if your foreign key column has a different name
        return $this->belongsTo(\App\Models\Staff\Staff::class, 'received_by');
    }
}




