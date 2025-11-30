<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class CustomException extends Exception
{
    public function __construct(string $message = "An error occurred", int $code = 400)
    {
        parent::__construct($message, $code);
    }

    // Format JSON response globally
    public function render($request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], $this->getCode());
    }

}
