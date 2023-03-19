<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'company_id','department_id','employee_id','leave_type_id','start_date','end_date',
        'reason','attachment','half_day','days','status'
    ];

    protected $casts = [
        'company_id'  => 'integer',
        'department_id'  => 'integer',
        'employee_id'  => 'integer',
        'leave_type_id'=>'integer',
        'half_day'     => 'integer',
    ];


    public function employee()
    {
        return $this->hasOne('App\Models\Employee', 'id', 'employee_id');
    }

    public function company()
    {
        return $this->hasOne('App\Models\Company', 'id', 'company_id');
    }

    public function department()
    {
        return $this->hasOne('App\Models\Department', 'id', 'department_id');
    }

    public function leave_type()
    {
        return $this->belongsTo('App\Models\LeaveType');
    }
}
