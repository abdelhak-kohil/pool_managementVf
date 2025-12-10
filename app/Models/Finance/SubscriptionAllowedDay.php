<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class SubscriptionAllowedDay extends Model
{
    

    protected $table = 'pool_schema.subscription_allowed_days';
    protected $primaryKey = null; // composite key (subscription_id + weekday_id)
    public $incrementing = false;
    public $timestamps = false; // you don’t have timestamps in this table

    protected $fillable = [
        'subscription_id',
        'weekday_id',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id', 'subscription_id');
    }

    public function weekday()
    {
        return $this->belongsTo(\App\Models\Activity\Weekday::class, 'weekday_id', 'weekday_id');
    }
}
