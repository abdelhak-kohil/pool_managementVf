<?php

namespace App\Modules\Sales\DTOs;

use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionData
{
    public function __construct(
        public int $member_id,
        public int $plan_id,
        public int $activity_id,
        public Carbon $start_date,
        public Carbon $end_date,
        public string $status,
        public array $slot_ids,
        public float $amount,
        public string $payment_method,
        public ?string $notes = null,
        public ?int $staff_id = null,
    ) {}

    public static function fromRequest(Request $request, ?int $staffId): self
    {
        return new self(
            member_id: (int) $request->input('member_id'),
            plan_id: (int) $request->input('plan_id'),
            activity_id: (int) $request->input('activity_id'),
            start_date: Carbon::parse($request->input('start_date')),
            end_date: Carbon::parse($request->input('end_date')),
            status: (string) $request->input('status'),
            slot_ids: $request->input('slot_ids', []),
            amount: (float) $request->input('amount'),
            payment_method: (string) $request->input('payment_method'),
            notes: $request->input('notes'),
            staff_id: $staffId,
        );
    }
}
