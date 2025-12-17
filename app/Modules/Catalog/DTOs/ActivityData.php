<?php

namespace App\Modules\Catalog\DTOs;

use Illuminate\Http\Request;

class ActivityData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $access_type,
        public ?string $color_code,
        public bool $is_active
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            description: $request->input('description'),
            access_type: $request->input('access_type'),
            color_code: $request->input('color_code'),
            is_active: $request->boolean('is_active', true)
        );
    }
}
