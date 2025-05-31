<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disposition extends Model
{
    use HasFactory;
    protected $table = 'alldisposition';
    protected $primaryKey = 'id';
	public $timestamps = false;
}
