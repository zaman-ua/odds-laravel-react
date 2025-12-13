<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    protected $fillable = [
        'sport_key',
        'group_name',
        'title',
        'description',
        'active',
        'has_outrights',
    ];

    protected $casts = [
        'active' => 'bool',
        'has_outrights' => 'bool',
    ];
}
