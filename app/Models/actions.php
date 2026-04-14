<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $table      = 'actions';
    protected $primaryKey = 'ID1';
    public    $timestamps = false;

    protected $fillable = [
        'ID1', 'ID', 'Year',
        'Action', 'Color', 'Qunt_Ac', 'On',
        'Machin', 'Hours', 'Date', 'NotesA',
        'Kelo', 'Actual', 'Tarkeb', 'Wash',
        'Electricity', 'Taghez', 'StopVar', 'Tabrer',
    ];
}
