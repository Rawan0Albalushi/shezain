<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:160'],
            'name_en' => ['required', 'string', 'max:160'],
            'description_ar' => ['required', 'string', 'max:5000'],
            'description_en' => ['required', 'string', 'max:5000'],
            'fabric_ar' => ['nullable', 'string', 'max:160'],
            'fabric_en' => ['nullable', 'string', 'max:160'],
            'price' => ['required', 'regex:/^\d+(\.\d{1,3})?$/'],
            'is_active' => ['sometimes', 'boolean'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.size' => ['required', 'string', 'max:20', 'distinct'],
            'variants.*.sku' => ['required', 'string', 'max:60', 'distinct'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:10000'],
            'images' => ['sometimes', 'array', 'max:6'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_images' => ['sometimes', 'array'],
            'remove_images.*' => ['integer'],
        ];
    }
}
