<?php

namespace App\Models;

use App\Enum\InputStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leads extends Model
{
    use HasFactory;
    protected $table = "form_inputs";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'patient_phone',
        'secondary_phone',
        'first_name',
        'last_name',
        'dob',
        'medicare_id',
        'address',
        'city',
        'state',
        'zip',
        'product_specs',
        'doctor_name',
        'patient_last_visit',
        'doctor_address',
        'doctor_phone',
        'doctor_fax',
        'doctor_npi',
        'recording_link',
        'comments',
        'status',
        'center_code_id',
        'insurance_id',
        'products_id',
        'user_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'dob' => 'date',
        'center_code_id' => 'integer',
        'insurance_id' => 'integer',
        'products_id' => 'integer',
        'status' => 'string',
        'transfer_status' => InputStatus::class,
    ];

    public function centerCode(): BelongsTo
    {
        return $this->belongsTo(CenterCode::class);
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function products(): BelongsTo
    {
        return $this->belongsTo(Products::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($lead) {
            if (!auth()->user()->hasRole('admin')) {
                abort(403, 'Only admins can delete leads');
            }
        });
    }

    // app/Models/User.php
    public function leads(): HasMany
    {
        return $this->hasMany(Leads::class);
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
