<?php

namespace Modules\Payment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\WebhookService;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function __invoke(string $provider, Request $request): JsonResponse
    {
        app(WebhookService::class)->webhook($request->all(), $provider);

        return response()->json(['ok' => true], Response::HTTP_OK);
    }

}
