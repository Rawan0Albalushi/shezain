<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $variants = $this->input('variants', []);

        if (! is_array($variants)) {
            return;
        }

        $this->merge([
            'variants' => array_map(function ($row) {
                if (! is_array($row)) {
                    return $row;
                }

                $hex = strtoupper(trim((string) ($row['color_hex'] ?? '')));

                return [
                    ...$row,
                    'size' => trim((string) ($row['size'] ?? '')),
                    'color_ar' => trim((string) ($row['color_ar'] ?? '')),
                    'color_en' => trim((string) ($row['color_en'] ?? '')),
                    'color_hex' => $hex === '' ? null : $hex,
                    'sku' => trim((string) ($row['sku'] ?? '')),
                ];
            }, $variants),
        ]);
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
            'variants.*.size' => ['required', 'string', 'max:20'],
            'variants.*.color_ar' => ['required', 'string', 'max:40'],
            'variants.*.color_en' => ['required', 'string', 'max:40'],
            'variants.*.color_hex' => ['nullable', 'regex:/^#[0-9A-F]{6}$/'],
            'variants.*.sku' => ['required', 'string', 'max:60', 'distinct'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:10000'],
            'images' => ['sometimes', 'array', 'max:6'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_images' => ['sometimes', 'array'],
            'remove_images.*' => ['integer'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('variants')) {
                    return;
                }

                $seen = [];
                $englishByArabic = [];
                $arabicByEnglish = [];

                foreach ($this->input('variants', []) as $index => $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $size = (string) ($row['size'] ?? '');
                    $colorAr = (string) ($row['color_ar'] ?? '');
                    $colorEn = (string) ($row['color_en'] ?? '');
                    $english = mb_strtolower($colorEn);
                    $pair = $size.'|'.$colorAr;

                    if (isset($seen[$pair])) {
                        $validator->errors()->add("variants.$index.color_ar", __('store.duplicate_color'));
                    }

                    $seen[$pair] = true;

                    if ($colorAr !== '' && $english !== '' && isset($englishByArabic[$colorAr]) && $englishByArabic[$colorAr] !== $english) {
                        $validator->errors()->add("variants.$index.color_en", __('store.color_en_mismatch'));
                    }

                    if ($colorAr !== '' && $english !== '' && isset($arabicByEnglish[$english]) && $arabicByEnglish[$english] !== $colorAr) {
                        $validator->errors()->add("variants.$index.color_ar", __('store.color_ar_mismatch'));
                    }

                    if ($colorAr !== '' && $english !== '' && ! isset($englishByArabic[$colorAr])) {
                        $englishByArabic[$colorAr] = $english;
                    }

                    if ($colorAr !== '' && $english !== '' && ! isset($arabicByEnglish[$english])) {
                        $arabicByEnglish[$english] = $colorAr;
                    }
                }
            },
        ];
    }
}
