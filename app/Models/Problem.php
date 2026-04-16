<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    protected $table      = 'Problems';  // ✅ تأكد من اسم الجدول الصحيح
    protected $primaryKey = 'ID1';       // ✅ غيّر من 'ID' إلى 'ID1'
    public    $timestamps = false;
    public    $incrementing = false;     // ✅ إذا كان ID1 غير auto-increment

    protected $fillable = [
        'ID1', 'ID', 'Year',          // ✅ أضف الحقول الأساسية
        'print_num', 'prod_date', 'exp_date', 'print_count',
    ];
}
