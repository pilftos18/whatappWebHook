<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{   
    
    use HasFactory;
    protected $table = 'clients';
    protected $primaryKey = 'id';
    protected $fillable = ['name','mobileno','email','description','status','deleted_at','created_by','updated_by'];

    // protected function runSoftDelete()
    // {
    //     $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());

    //     $this->{$this->getDeletedAtColumn()} = $time = $this->freshTimestamp();
    //     $this->{$this->getDelStatusColumn()} = 2;

    //     $query->update([
    //         $this->getDeletedAtColumn() => $this->fromDateTime($time),
    //         $this->getDelStatusColumn() => 2,
    //     ]);
    // }

    // public function getDelStatusColumn()
    // {
    //    return 'deleted_at';
    // }
}
