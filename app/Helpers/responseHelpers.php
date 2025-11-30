<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

if (!function_exists('successResponse')) {
    function successResponse( string $message = 'Success', $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}


if (!function_exists('failureResponse')) {
    function failureResponse(string $message = 'error', int $status = 400, $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}

if (!function_exists('customException')) {
    function customException(Throwable $exception, string $context = ''): JsonResponse
    {
        Log::error("{$context} Exception", [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        return failureResponse('An unexpected error occurred', 500);
    }
}

if (!function_exists('mydd')) {
    function mydd($data)
    {
        echo "<pre style='background:#111;color:#0f0;padding:10px;border-radius:5px'>";
        print_r($data);
        echo "</pre>";
        die();
    }
}
