<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commissions extends Model
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'team',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Leads::class, 'user_id');
    }
    public function billedLeads(): HasMany
    {
        return $this->hasMany(Leads::class)->where('status', 'billable');
    }

    public function returnLeads(): HasMany
    {
        return $this->hasMany(Leads::class)->where('status', 'returned');
    }
}
