<?php

namespace Modules\Auth\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */

    public function rules(): array
    {
        $user = auth('api')->user();
        return [
            'firstname' => ['sometimes', 'string', 'max:255'],
            'lastname' => ['sometimes', 'string', 'max:255'],
            'username' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->username)
            ],

//            'phone_number' => [
//                'sometimes',
//                'string',
//                'max:20',
//                Rule::unique('users', 'phone_number')->ignore($user->phone_number),
//            ],
//            'dial_code' => ['sometimes', 'string', 'max:10'],
//            'country' => ['sometimes', 'string', 'max:100'],
            'phone_number' => [
                'sometimes',
                'string',
                'regex:/^[0-9]{6,15}$/', // digits only, realistic length
                Rule::unique('users', 'phone_number')
                    ->where(fn ($q) => $q->where('dial_code', request('dial_code')))->ignore($user->phone_number)
            ],

            'dial_code' => [
                'sometimes',
                'string',
                'regex:/^\+\d{1,4}$/', // +234, +1, +44 etc
            ],
            'country_id' => ['sometimes', 'string', Rule::exists('countries', 'id')],
        ];
    }

    /**
     * Optional: Customize error messages
     */
    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken.',
            'phone_number.unique' => 'This phone number is already in use.',
            'profile_picture.image' => 'Profile picture must be an image file.',
            'profile_picture.max' => 'Profile picture size cannot exceed 2MB.',
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
