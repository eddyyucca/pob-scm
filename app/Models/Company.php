<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'is_active'];

    public function pobEntries()
    {
        return $this->hasMany(PobEntry::class);
    }

    public function latestEntry()
    {
        return $this->hasOne(PobEntry::class)->latestOfMany('date');
    }
}
