<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{

    protected $fillable = [
        'currency_id', 'email', 'CompanyName', 'CompanyPhone', 'CompanyAdress',
         'logo','footer','developed_by','client_id','warehouse_id','default_language',
         'is_invoice_footer','invoice_footer',
    ];

    protected $casts = [
        'currency_id' => 'integer',
        'client_id' => 'integer',
        'is_invoice_footer' => 'integer',
        'warehouse_id' => 'integer',
    ];

    public function Currency()
    {
        return $this->belongsTo('App\Models\Currency');
    }

}
