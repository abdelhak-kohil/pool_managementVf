<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\Auditable;


class Role extends Model
{

     


    protected $table = 'pool_schema.roles';
    protected $primaryKey = 'role_id';
    public $timestamps = false;

    protected $fillable = ['role_name'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'pool_schema.role_permissions',
            'role_id',
            'permission_id'
        );
    }

    public function staff()
    {
        return $this->hasMany(\App\Models\Staff\Staff::class, 'role_id');
    }
}
