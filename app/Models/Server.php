<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{

    protected $fillable = [
        'mail_mailer','sender_name','host', 'port', 'username', 'password', 'encryption',
    ];

    protected $casts = [
        'port' => 'integer',
    ];

}
