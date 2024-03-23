<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
$table->string('ip_port')->nullable();
$table->string('type')->nullable();
$table->string('location')->nullable();
$table->boolean('status')->nullable();
$table->integer('timeout')->nullable();
$table->string('ext_ip')->nullable();
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
