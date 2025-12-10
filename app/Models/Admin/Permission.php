<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;


class Permission extends Model
{

    
    
    protected $table = 'pool_schema.permissions';
    protected $primaryKey = 'permission_id';
    public $timestamps = false;

    protected $fillable = ['permission_name'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'pool_schema.role_permissions', 'permission_id', 'role_id');
    }

}
