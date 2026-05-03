<?php

namespace Modules\BillPayment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BillPayment\actions\InitiateBillPaymentAction;
use Modules\BillPayment\actions\VerifyBillPaymentAction;
use Modules\BillPayment\app\Http\Requests\InitiateBillPaymentRequest;
use Modules\BillPayment\Enums\BillStatusEnum;
use Modules\BillPayment\Models\BillPayment;

class BillPaymentController extends Controller
{
    public function initiate(
        InitiateBillPaymentRequest $request,
        InitiateBillPaymentAction $action
    ): JsonResponse {
        $billPayment = $action->handle(
            data:   $request->validated(),
            userId: $request->user()->id,
        );

        return response()->json([
            'message' => 'Bill payment initiated.',
            'data'    => [
                'reference' => $billPayment->reference,
                'status'    => $billPayment->status,
                'amount'    => $billPayment->amount,
            ],
        ], 202);
    }

    public function status(int $id, VerifyBillPaymentAction $verify): JsonResponse
    {
        $billPayment = BillPayment::where('id', $id)
            ->where('user_id', auth('api')->id())
            ->firstOrFail();

        if ($billPayment->status === BillStatusEnum::PROCESSING
            && $billPayment->updated_at->diffInMinutes(now()) >= 2) {
            $billPayment = $verify->handle($billPayment);
        }

        return response()->json([
            'data' => [
                'reference'          => $billPayment->reference,
                'status'             => $billPayment->status,
                'amount'             => $billPayment->amount,
                'currency'           => $billPayment->currency,
                'type'               => $billPayment->type,
                'recipient'          => $billPayment->recipient,
                'token'              => $billPayment->token,
                'provider_reference' => $billPayment->provider_reference,
                'processed_at'       => $billPayment->processed_at,
                'created_at'         => $billPayment->created_at,
            ],
        ]);
    }

    public function history(): JsonResponse
    {
        $payments = BillPayment::where('user_id', auth('api')->id())
            ->select([
                'id', 'reference', 'type', 'status',
                'amount', 'currency', 'recipient',
                'token', 'provider_reference',
                'processed_at', 'created_at',
            ])
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $payments]);
    }
}
