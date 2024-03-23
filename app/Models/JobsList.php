<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobsList extends Model
{
    use HasFactory;

    protected $table = 'jobs_list';

    protected $fillable = [
        'uuid',
        'started_at',
        'ended_at',
        'total_count',
        'working_count'
    ];
}
