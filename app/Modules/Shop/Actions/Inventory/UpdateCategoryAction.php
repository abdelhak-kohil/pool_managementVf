<?php

namespace App\Modules\Shop\Actions\Inventory;

use App\Modules\Shop\DTOs\CategoryData;
use App\Models\Shop\Category;

class UpdateCategoryAction
{
    public function execute(Category $category, CategoryData $data): void
    {
        $category->update([
            'name' => $data->name,
            'type' => $data->type,
        ]);
    }
}
