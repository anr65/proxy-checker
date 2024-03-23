<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Proxy extends Model
{
    use HasFactory;

    protected $table = 'proxies';

    protected $fillable = [
        'ip_port',
        'type',
        'location',
        'status',
        'timeout',
        'ext_ip'
    ];
}
