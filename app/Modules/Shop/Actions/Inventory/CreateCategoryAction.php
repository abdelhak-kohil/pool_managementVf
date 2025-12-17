<?php

namespace App\Modules\Shop\Actions\Inventory;

use App\Modules\Shop\DTOs\CategoryData;
use App\Models\Shop\Category;

class CreateCategoryAction
{
    public function execute(CategoryData $data): Category
    {
        return Category::create([
            'name' => $data->name,
            'type' => $data->type,
        ]);
    }
}
