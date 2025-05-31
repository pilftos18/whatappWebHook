<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session_Log extends Model
{
    use HasFactory;
    protected $table = 'session_log';
    // protected $primaryKey = 'id';
    // protected $fillable = ['id', 'user_id', 'session_id','ip_address','login_status','status','created_at','updated_at'];


}
