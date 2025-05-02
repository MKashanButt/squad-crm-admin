<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Impersonate;

    // Define the enum values (adjust according to your actual enum)
    public const ROLE_ADMIN = 'admin';
    public const ROLE_AGENT = 'agent';
    public const ROLE_MANAGER = 'manager';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'team',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
    // Check if user has a specific role
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // Optional: Helper methods for specific roles
    public function isAgent(): bool
    {
        return $this->hasRole(self::ROLE_AGENT);
    }

    public function canImpersonate(): bool
    {
        return $this->hasRole('admin');
    }

    public function canBeImpersonated(): bool
    {
        // Don't allow impersonating other admins
        return !$this->hasRole('admin') && $this->id !== auth()->id();
    }
}
