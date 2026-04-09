<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table      = 'vouchers';
    protected $primaryKey = 'ID';
    public    $timestamps = false;

    protected $fillable = [
        'ID', 'ID1', 'Year', 'Voucher_num', 'V_date', 'V_Qunt',
        'Bill_Num', 'Contean', 'Paking_q',
        'Box_tp', 'Box_L', 'Box_W', 'Box_H', 'Note_V',
    ];
}
