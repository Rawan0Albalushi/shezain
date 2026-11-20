<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::when($this->boolean('create_account') && ! $this->user(), ['unique:users,email']),
            ],
            'governorate_id' => ['required', 'integer', 'exists:shipping_rates,id'],
            'city' => ['required', 'string', 'max:120'],
            'address_line' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'save_address' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'address_label' => ['nullable', 'string', 'max:40'],
            'create_account' => ['sometimes', 'boolean'],
            'password' => [
                Rule::requiredIf(fn () => $this->boolean('create_account') && ! $this->user()),
                'nullable',
                'confirmed',
                Password::min(8),
            ],
        ];
    }
}
