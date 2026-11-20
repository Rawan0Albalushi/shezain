<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['governorate_ar', 'governorate_en', 'fee', 'is_active', 'sort'])]
class ShippingRate extends Model
{
    protected function casts(): array
    {
        return [
            'fee' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
