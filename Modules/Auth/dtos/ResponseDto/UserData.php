<?php

namespace Modules\Auth\dtos\ResponseDto;

use Modules\Auth\enums\KycStatusEnum;
use Modules\Auth\enums\UserStatusEnum;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $email,
        public readonly string $country,
        public readonly string $username,
        public readonly string $phone_number,
        public readonly UserStatusEnum $status,
        public readonly KycStatusEnum $kyc_status,

    ){}
}
