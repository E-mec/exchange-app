<?php

namespace Modules\BillPayment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\BillPayment\actions\InitiateBillPaymentAction;
use Modules\BillPayment\app\Http\Requests\InitiateBillPaymentRequest;
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

    public function status(int $id): JsonResponse
    {
        $billPayment = BillPayment::where('id', $id)
            ->where('user_id', request()->user()->id)
            ->firstOrFail();

        return response()->json(['data' => $billPayment]);
    }

    public function history(): JsonResponse
    {
        $payments = BillPayment::where('user_id', request()->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json(['data' => $payments]);
    }
}
