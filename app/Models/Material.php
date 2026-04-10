<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table      = 'materials';
    protected $primaryKey = '_ID';
    public    $timestamps = false;

    protected $fillable = [
        'ID', 'Year',
        'type', 'source', 'supplier',
        'length', 'width', 'gram', 'at_plates',
        'last_date', 'output', 'notes',
    ];
}
