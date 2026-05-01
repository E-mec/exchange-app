<?php

namespace Modules\Wallet\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Wallet\enums\CurrencyEnum;

class TransferRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            "from_currency" => ["required", Rule::enum(CurrencyEnum::class)],
            "to_currency" => ["required", Rule::enum(CurrencyEnum::class)],
            "amount" => ["required", "numeric"],
            "to_user_id" => ["required", Rule::exists("users", "id"), Rule::notIn([auth()->id()])],
            "idempotency_key" => ["required", "uuid"],
            'pin' => ['required', 'digits:6'],

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
