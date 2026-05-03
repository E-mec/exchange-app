<?php

namespace Modules\Auth\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => [
                'required',
                'string',
                'regex:/^[0-9]{6,15}$/', // digits only, realistic length
                Rule::unique('users', 'phone_number')
                    ->where(fn ($q) => $q->where('dial_code', request('dial_code')))
            ],

            'dial_code' => [
                'required',
                'string',
                'regex:/^\+\d{1,4}$/', // +234, +1, +44 etc
            ],
            'country_id' => ['required', 'string', Rule::exists('countries', 'id')],
            'pin' => ['nullable', 'digits:6', 'confirmed'],
            'referred_by' => ['nullable', Rule::exists('users', 'id')],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    /**
     * Optional: Customize error messages
     */
    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email is already registered.',
            'phone_number.unique' => 'This phone number is already in use.',
            'password.confirmed' => 'Passwords do not match.',
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
