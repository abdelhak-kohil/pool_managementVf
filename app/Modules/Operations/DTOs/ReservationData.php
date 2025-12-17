<?php

namespace App\Modules\Operations\DTOs;

use Illuminate\Http\Request;

class ReservationData
{
    public function __construct(
        public int $slot_id,
        public string $reservation_type, // 'member_private', 'partner_group'
        public ?int $member_id,
        public ?int $partner_group_id,
        public ?string $notes
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            slot_id: (int) $request->input('slot_id'),
            reservation_type: $request->input('reservation_type'),
            member_id: $request->input('member_id'),
            partner_group_id: $request->input('partner_group_id'),
            notes: $request->input('notes')
        );
    }
}
