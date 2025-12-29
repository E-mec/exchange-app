<?php

namespace Modules\Wallet\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Wallet\enums\CurrencyEnum;

class InitiateDepositRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'currency' => ['required', 'string', Rule::enum(CurrencyEnum::class)],
            // @todo use an enum for the validation below
            'deposit_channel' => ['required',  'string', 'in:paystack,flutterwave,bank,paypal']
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
