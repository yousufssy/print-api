<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MasterWSeeder extends Seeder
{
    public function run()
    {
        // قراءة محتوى ملف SQL
        $sql = File::get(database_path('MasterW_test_data.sql'));

        // تنفيذ كل الأوامر الموجودة في الملف
        DB::statement($sql);
    }
}