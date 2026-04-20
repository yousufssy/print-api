<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Problemcu extends Model
{
    protected $table = 'problemcu';

    // ❌ لا يوجد primary key مفرد
    protected $primaryKey = null;

    public $incrementing = false;
    public $timestamps   = false;

    protected $keyType = 'int';

    protected $fillable = [
        'ID',
        'year',
        'ta3lel',
        'tlf',
        'err',
        'incre',
        'dn'
    ];
}
