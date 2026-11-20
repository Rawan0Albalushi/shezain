<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'phone', 'body', 'read_at'])]
class Message extends Model
{
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }
}
