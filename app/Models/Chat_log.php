<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat_log extends Model
{
    use HasFactory;
    protected $table = 'chat_log';
    protected $primaryKey = 'id';
    protected $guarded = [];
}
