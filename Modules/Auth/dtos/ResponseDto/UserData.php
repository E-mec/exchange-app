<?php

namespace Modules\Auth\dtos\ResponseDto;

use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $firstname,
        public readonly string $lastname,
        public readonly string $email,
        public readonly string $country,

    ){}
}
