<?php

namespace App\Http\Controllers;

use App\dtos\CountryData;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return successResponse('countries', CountryData::collect(Country::all()));
    }
}
