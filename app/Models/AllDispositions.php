<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllDispositions extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $table = 'alldisposition';
    protected $guarded = [];
}
