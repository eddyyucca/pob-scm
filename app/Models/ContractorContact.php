<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ContractorContact extends Model {
    protected $fillable = ['company_id','name','phone','position','is_active'];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    // Format nomor ke 62xxx
    public function getFormattedPhoneAttribute(): string {
        $p = preg_replace('/\D/', '', $this->phone);
        if (str_starts_with($p, '0')) $p = '62' . substr($p, 1);
        return $p;
    }
}
