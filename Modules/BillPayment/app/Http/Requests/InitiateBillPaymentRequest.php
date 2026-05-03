<?php

namespace Modules\BillPayment\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\BillPayment\Models\BillService;

class InitiateBillPaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $service = BillService::active()
            ->where('slug', $this->service_slug)
            ->first();

        return [
            'service_slug'     => ['required', 'string', 'exists:bill_services,slug'],
            'recipient'        => ['required', 'string'],
            'amount' => array_filter([
                'required', 'numeric',
                $service?->min_amount ? 'min:' . $service->min_amount : 'min:1',
                $service?->max_amount ? 'max:' . $service->max_amount : null,
            ]),
            'currency'         => ['required', 'string', 'size:3'],
            'wallet_id' => [
                'required',
                'integer',
                Rule::exists('wallets', 'id')->where('user_id', $this->user()->id),
            ],
            'variation_code' => $service?->has_variations
                ? ['required', 'string',
                    Rule::exists('bill_service_variations', 'variation_code')
                        ->where('bill_service_id', $service->id)
                        ->where('is_active', true)]
                : ['sometimes', 'nullable', 'string'],
            'idempotency_key'  => ['required', 'string', 'max:100'],
            'meta'             => ['sometimes', 'array'],
            'meta.phone'       => ['sometimes', 'string'],
            'meta.meter_type'  => ['sometimes', 'string', 'in:prepaid,postpaid'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
