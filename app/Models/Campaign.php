<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $table = 'campaign';
    use HasFactory;

    protected $fillable = [
        'name',
        'info',
    ];

    protected $casts = [
        'id' => 'integer',
    ];
}
