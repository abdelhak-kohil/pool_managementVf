<?php

namespace App\Modules\Catalog\Actions;

use App\Models\Activity\Activity;
use App\Modules\Catalog\DTOs\ActivityData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateActivityAction
{
    public function execute(ActivityData $data): Activity
    {
        return DB::transaction(function () use ($data) {
            return Activity::create([
                'name'        => $data->name,
                'description' => $data->description,
                'access_type' => $data->access_type,
                'color_code'  => $data->color_code,
                'is_active'   => $data->is_active,
            ]);
        });
    }
}
