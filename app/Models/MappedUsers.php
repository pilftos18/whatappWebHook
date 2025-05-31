<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MappedUsers extends Model
{
    use HasFactory;
    protected $table = 'queue_mapping';
    protected $primaryKey = 'id';
    protected $guarded = [];
}
