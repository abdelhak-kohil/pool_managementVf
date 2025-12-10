<?php

namespace App\Models\Activity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Finance\Plan;

class ActivityPlanPrice extends Model
{
    use HasFactory;

    protected $table = 'pool_schema.activity_plan_prices';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'activity_id',
        'plan_id',
        'price'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'activity_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id', 'plan_id');
    }
}
