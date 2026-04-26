<?php

namespace Modules\Wallet\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Wallet\enums\CurrencyEnum;

class WithdrawalRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', Rule::enum(CurrencyEnum::class)],
            'destination' => ['required', 'array'],
            'idempotency_key' => ['required', 'uuid'],
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
