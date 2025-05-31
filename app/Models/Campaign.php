<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;
    protected $table = 'campaign';
    protected $primaryKey = 'id';

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
    //    return 'del_status';
    // }
}
