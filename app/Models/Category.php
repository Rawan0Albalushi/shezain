<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name_ar', 'name_en', 'description_ar', 'description_en', 'sort'])]
class Category extends Model
{
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
