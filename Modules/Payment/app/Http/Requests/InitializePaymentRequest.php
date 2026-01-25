<?php

namespace Modules\Payment\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\Payment\Enums\PaymentProviderEnum;

class InitializePaymentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', new Enum(PaymentProviderEnum::class)],
            'amount'   => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'email'    => ['required', 'email'],
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
