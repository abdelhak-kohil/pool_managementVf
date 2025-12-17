<?php

namespace App\Modules\Shop\DTOs;

use Illuminate\Http\Request;

class CategoryData
{
    public function __construct(
        public string $name,
        public string $type
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            type: $request->input('type')
        );
    }
}
