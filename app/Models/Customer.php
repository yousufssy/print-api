<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table      = 'customer2';
    protected $primaryKey = 'row_id';
    public    $timestamps = false;

    protected $fillable = ['Customer', 'Activety'];
}
