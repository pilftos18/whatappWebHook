<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'api_list';
    protected $primaryKey = 'id';
    protected $fillable = ['apiname', 'company','vendorname','client_id','status','created_by','updated_by'];
    //  'apiurl','apitesturl',
    protected function runSoftDelete()
    {
        $query = $this->newModelQuery()->where($this->getKeyName(), $this->getKey());

        $this->{$this->getDeletedAtColumn()} = $time = $this->freshTimestamp();
        $this->{$this->getDelStatusColumn()} = 2;

        $query->update([
            $this->getDeletedAtColumn() => $this->fromDateTime($time),
            $this->getDelStatusColumn() => 2,
        ]);
    }

    public function getDelStatusColumn()
    {
        return 'del_status';
    }
}
