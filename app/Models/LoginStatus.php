<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginStatus extends Model
{
    use HasFactory;
    protected $table = 'login_status';
    protected $primaryKey = 'id';
}
