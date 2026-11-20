<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    public static function content(): array
    {
        $keys = [
            'topline_ar', 'topline_en',
            'hero_title_ar', 'hero_title_en',
            'hero_intro_ar', 'hero_intro_en',
            'hero_button_ar', 'hero_button_en',
            'hero_foot_ar', 'hero_foot_en',
            'collection_kicker_ar', 'collection_kicker_en',
            'brand_title_ar', 'brand_title_en',
            'brand_text_ar', 'brand_text_en',
            'phone', 'email',
        ];

        $stored = static::query()->whereIn('key', $keys)->pluck('value', 'key');

        return collect($keys)->mapWithKeys(fn (string $key) => [$key => (string) ($stored[$key] ?? '')])->all();
    }
}
