<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallWindow extends Model
{
    use HasFactory;
    protected $table = 'call_window';
    protected $primaryKey = 'id';
}
