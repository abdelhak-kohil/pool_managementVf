<?php

namespace App\Models\Pool;

use Illuminate\Database\Eloquent\Model;

class TaskTemplate extends Model
{
    protected $table = 'pool_schema.task_templates';

    protected $fillable = [
        'name',
        'type', // daily, weekly, monthly
        'items', // JSON definition of fields
        'is_active',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];
}
