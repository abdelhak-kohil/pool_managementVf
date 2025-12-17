<?php

namespace App\Modules\HR\DTOs;

use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffData
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $username,
        public int $role_id,
        public bool $is_active,
        public ?string $password, // Nullable for updates
        public ?string $email,
        public ?string $phone_number,
        public ?string $specialty,
        public ?Carbon $hiring_date,
        public ?string $salary_type,
        public ?float $hourly_rate,
        public ?string $notes,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            first_name: $request->input('first_name'),
            last_name: $request->input('last_name'),
            username: strtolower($request->input('username')),
            role_id: (int) $request->input('role_id'),
            is_active: $request->has('is_active'), // Checkbox usually
            password: $request->input('password'),
            email: $request->input('email'),
            phone_number: $request->input('phone_number'),
            specialty: $request->input('specialty'),
            hiring_date: $request->input('hiring_date') ? Carbon::parse($request->input('hiring_date')) : null,
            salary_type: $request->input('salary_type'),
            hourly_rate: $request->input('hourly_rate') ? (float) $request->input('hourly_rate') : null,
            notes: $request->input('notes'),
        );
    }
}
