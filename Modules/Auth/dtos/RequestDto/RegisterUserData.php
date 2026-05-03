<?php

namespace Modules\Auth\dtos\RequestDto;

use Illuminate\Http\UploadedFile;

class RegisterUserData
{
    public function __construct(
        public string $firstname,
        public string $lastname,
        public string $email,
        public string $username,
        public string $phone_number,
        public string $dial_code,
        public int $country_id,
        public string $password,
        public ?string $pin = null,
        public ?int $referred_by = null,
        public ?UploadedFile $profile_picture = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            firstname: $data['firstname'],
            lastname: $data['lastname'],
            email: $data['email'],
            username: $data['username'],
            phone_number: $data['phone_number'],
            dial_code: $data['dial_code'],
            country_id: $data['country_id'],
            password: $data['password'],
            pin: $data['pin'] ?? null,
            referred_by: $data['referred_by'] ?? null,
            profile_picture: $data['profile_picture'] ?? null
        );
    }
}
