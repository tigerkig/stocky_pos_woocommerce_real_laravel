<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeShift extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name','company_id','monday_in','monday_out',
        'tuesday_in','tuesday_out','wednesday_in','wednesday_out',
        'thursday_in','thursday_out','friday_in','friday_out',
        'saturday_in','saturday_out','sunday_in','sunday_out'

    ];

    protected $casts = [
        'company_id'  => 'integer',
    ];


    public function company()
    {
        return $this->hasOne('App\Models\Company', 'id', 'company_id');
    }


}
