<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id('_ID');
            $table->integer('ID')->nullable();          // order ID
            $table->integer('Year')->nullable();
            $table->string('type',      100)->nullable();
            $table->string('source',    100)->nullable();
            $table->string('supplier',  100)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width',  10, 2)->nullable();
            $table->decimal('gram',   10, 2)->nullable();
            $table->decimal('at_plates', 10, 2)->nullable();
            $table->date('last_date')->nullable();
            $table->string('output',    200)->nullable();
            $table->string('notes',     500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
