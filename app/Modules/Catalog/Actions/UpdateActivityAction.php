<?php

namespace App\Modules\Catalog\Actions;

use App\Models\Activity\Activity;
use App\Modules\Catalog\DTOs\ActivityData;
use Illuminate\Support\Facades\DB;

class UpdateActivityAction
{
    public function execute(Activity $activity, ActivityData $data): Activity
    {
        return DB::transaction(function () use ($activity, $data) {
            $activity->update([
                'name'        => $data->name,
                'description' => $data->description,
                'access_type' => $data->access_type,
                'color_code'  => $data->color_code,
                'is_active'   => $data->is_active,
            ]);

            return $activity->refresh();
        });
    }
}
