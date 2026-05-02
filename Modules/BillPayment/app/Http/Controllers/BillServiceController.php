<?php

namespace Modules\BillPayment\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\BillPayment\Enums\BillTypeEnum;
use Modules\BillPayment\Models\BillService;

class BillServiceController extends Controller
{
    /**
     * GET /api/bills/services
     * List all active services, optionally filtered by type.
     *
     * ?type=airtime | data | electricity | tv | internet | water | betting
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['sometimes', 'string', 'in:' . collect(BillTypeEnum::cases())->pluck('value')->implode(',')],
        ]);

        $services = BillService::active()
            ->when($request->filled('type'), fn ($q) =>
            $q->ofType(BillTypeEnum::from($request->type))
            )
            ->select([
                'id', 'slug', 'name', 'type', 'provider',
                'min_amount', 'max_amount',
                'has_variations', 'requires_validation',
                'image_url',
            ])
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $services,
        ]);
    }

    /**
     * GET /api/bills/services/{slug}
     * Get a single service with its variations.
     */
    public function show(string $slug): JsonResponse
    {
        $service = BillService::active()
            ->where('slug', $slug)
            ->with('variations')
            ->firstOrFail();

        return response()->json([
            'data' => $service,
        ]);
    }

    /**
     * GET /api/bills/services/{slug}/variations
     * List active variations for a service.
     */
    public function variations(string $slug): JsonResponse
    {
        $service = BillService::active()
            ->where('slug', $slug)
            ->where('has_variations', true)
            ->firstOrFail();

        return response()->json([
            'data' => $service->variations,
        ]);
    }
}
