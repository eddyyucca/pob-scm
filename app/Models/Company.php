<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Company extends Model {
    protected $fillable = ['name', 'slug', 'code', 'type', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function pobEntries()  { return $this->hasMany(PobEntry::class); }
    public function employees()   { return $this->hasMany(PobEmployee::class); }
    public function contacts()    { return $this->hasMany(ContractorContact::class); }
}
