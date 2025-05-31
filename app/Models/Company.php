<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Company extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'clients';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'email','max_count','envtype','status','created_by','updated_by'];
    // 'website','file',

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
