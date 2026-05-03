<?php

namespace App\dtos;

use Spatie\LaravelData\Data;

class CountryData extends Data
{
    public function __construct(
        public string $name,
        public string $code,
        public string $dial_code,
    ){}
}
