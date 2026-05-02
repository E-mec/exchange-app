<?php

namespace Modules\BillPayment\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateBillPaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'service_slug'     => ['required', 'string', 'exists:bill_services,slug'],
            'recipient'        => ['required', 'string'],
            'amount'           => ['required', 'numeric', 'min:1'],
            'currency'         => ['required', 'string', 'size:3'],
            'wallet_id'        => ['required', 'integer', 'exists:wallets,id'],
            'variation_code'   => ['sometimes', 'nullable', 'string'],
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
