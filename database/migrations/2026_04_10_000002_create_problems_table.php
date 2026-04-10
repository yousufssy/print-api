<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problems', function (Blueprint $table) {
            $table->id('_ID');
            $table->integer('ID')->nullable();          // order ID
            $table->integer('Year')->nullable();
            $table->string('print_num',   100)->nullable();
            $table->date('prod_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->integer('print_count')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('problems');
    }
};
