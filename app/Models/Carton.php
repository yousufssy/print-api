<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carton extends Model
{
    protected $table = 'your_table_name';

    protected $fillable = [
        'ID1',
        'ID',
        'Type1',
        'Id_carton',
        'Source1',
        'Supplier1',
        'Long1',
        'Width1',
        'Gramage1',
        'Sheet_count1',
        'Out_Date',
        'Out_ord_num',
        'note_crt',
        'year',
        'Price'
    ];

    public $timestamps = false;
}
