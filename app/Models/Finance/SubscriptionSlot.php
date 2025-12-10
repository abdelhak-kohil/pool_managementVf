<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use App\Models\Activity\TimeSlot;

class SubscriptionSlot extends Model
{
    protected $table = 'pool_schema.subscription_slots';
    protected $primaryKey = 'subscription_slot_id';

    public $timestamps = true;

    protected $fillable = [
        'subscription_id',
        'slot_id',
    ];

    // 🔗 Relations
    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function slot()
    {
        return $this->belongsTo(TimeSlot::class, 'slot_id');
    }

    
}