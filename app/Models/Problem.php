<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    protected $table      = 'problems';
    protected $primaryKey = '_ID';
    public    $timestamps = false;

    protected $fillable = [
        'ID', 'Year',
        'print_num', 'prod_date', 'exp_date', 'print_count',
    ];
}
