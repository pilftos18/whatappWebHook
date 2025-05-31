<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Users extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id',
        'role',
        'client_id',        
        'name',
        'mobile',
        'email',
        'username',
        'password',
        'status',
        'gender',
        'created_at',
        'manager_id',    // Nullable
        'supervisor_id',  // Nullable
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     * 
     */
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getStatusAttribute($value)
    {
        return $value == 1 || $value == 0 ? 'Active' : 'Inactive';
    }
    public function getRoleAttribute($value)
    {
        if($value == 'user'){
            return 'User';
        }else if($value == 'admin'){
        return 'Admin';
        }else if($value == 'mis'){
            return 'Mis';
        }else if($value == 'manager'){
            return 'Manager';
        }
        else if($value == 'supervisor'){
            return 'Supervisor';
        }
    }
    
    public function getClientidameAttribute()
    {
        return $this->client_id  = ($this->client_id == 1) ? 'Authbridge' : 'Nothing';
    }
}
