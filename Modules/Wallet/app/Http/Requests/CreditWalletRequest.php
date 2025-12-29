<?php

namespace Modules\Wallet\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Wallet\enums\CurrencyEnum;

class CreditWalletRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'walletId' => ['required', 'integer', 'exists:wallets,id'],
            'currency' => ['required', 'string', Rule::enum(CurrencyEnum::class)],
            'idempotencyKey' => ['nullable', 'string'],
            'amount' => ['required', 'string', 'min:1'],
            'meta' => ['nullable', 'array'],
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
