<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PobEmployee extends Model
{
    protected $fillable = [
        'pob_entry_id', 'company_id', 'date',
        'id_number', 'id_type', 'name',
        'position', 'department', 'employee_type',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function pobEntry()
    {
        return $this->belongsTo(PobEntry::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
