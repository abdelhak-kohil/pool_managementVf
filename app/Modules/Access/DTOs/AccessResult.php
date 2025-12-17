<?php

namespace App\Modules\Access\DTOs;

class AccessResult
{
    public function __construct(
        public bool $isGranted,
        public string $message,
        public ?string $personName = null,
        public ?string $personType = null, // 'Member' or 'Staff'
        public ?string $planName = null,
        public ?string $photoUrl = null,
        public ?string $expiryDate = null,
        public ?int $memberId = null,
        public ?int $staffId = null,
        public ?int $remainingSessions = null,
    ) {}
    
    public static function granted($message, $personName, $personType, $photoUrl, $planName = null, $expiryDate = null, $memberId = null, $staffId = null, $remainingSessions = null): self
    {
        return new self(true, $message, $personName, $personType, $planName, $photoUrl, $expiryDate, $memberId, $staffId, $remainingSessions);
    }

    public static function denied($message, $personName = null, $personType = null, $photoUrl = null, $memberId = null, $staffId = null, $remainingSessions = null): self
    {
        return new self(false, $message, $personName, $personType, null, $photoUrl, null, $memberId, $staffId, $remainingSessions);
    }
}
