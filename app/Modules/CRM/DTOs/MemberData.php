<?php

namespace App\Modules\CRM\DTOs;

use Illuminate\Http\Request;
use Carbon\Carbon;

class MemberData
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public ?string $email,
        public ?string $phone_number,
        public ?Carbon $date_of_birth,
        public ?string $address,
        public ?string $emergency_contact_name,
        public ?string $emergency_contact_phone,
        public ?string $notes,
        public ?string $health_conditions,
        // Badge info
        public ?string $badge_uid = null,
        public ?string $badge_status = null,
        // Optional: File upload handling usually stays in controller or passed as SplFileInfo, 
        // but for simplicity we'll handle the path string here if already stored, 
        // OR pass the UploadedFile object if we want the action to handle storage.
        // Let's pass the UploadedFile or null, but DTOs should ideally be serializable.
        // We'll handle file upload in Controller for now and pass the path, OR pass the file object.
        // Let's pass the file object or null to let Action handle storage (better encapsulation).
        public mixed $photo = null, 
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            first_name: $request->input('first_name'),
            last_name: $request->input('last_name'),
            email: $request->input('email'),
            phone_number: $request->input('phone_number'),
            date_of_birth: $request->input('date_of_birth') ? Carbon::parse($request->input('date_of_birth')) : null,
            address: $request->input('address'),
            emergency_contact_name: $request->input('emergency_contact_name'),
            emergency_contact_phone: $request->input('emergency_contact_phone'),
            notes: $request->input('notes'),
            health_conditions: $request->input('health_conditions'),
            badge_uid: $request->input('badge_uid'),
            badge_status: $request->input('badge_status'),
            photo: $request->file('photo')
        );
    }
}
