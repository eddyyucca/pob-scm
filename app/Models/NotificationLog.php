<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model {
    protected $fillable = ['company_id','phone','recipient_name','message','status','response','sent_at'];
    protected $casts    = ['sent_at' => 'datetime'];

    public function company() {
        return $this->belongsTo(Company::class);
    }
}
