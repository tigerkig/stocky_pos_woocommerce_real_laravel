<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'designation','department_id','company_id'
    ];

    protected $casts = [
        'department_id' => 'integer',
        'company_id'    => 'integer',
    ];


    public function company()
    {
        return $this->hasOne('App\Models\Company', 'id', 'company_id');
    }

    public function department()
    {
        return $this->hasOne('App\Models\Department', 'id', 'department_id');
    }
}
