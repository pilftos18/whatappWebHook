<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Break_log extends Model
{
    use HasFactory;
    protected $table = 'break_log';

    protected $fillable = ['client_id','break_id', 'break_name','user_id','start_time','end_time'];
}
