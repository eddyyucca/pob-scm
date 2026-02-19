<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PobEntry extends Model
{
    protected $fillable = [
        'company_id', 'date', 'total_pob', 'total_manpower',
        'informed_by', 'contact_wa', 'submitted_by', 'submitted_email'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
