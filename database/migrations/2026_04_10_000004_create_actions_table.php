<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->id('_ID');
            $table->integer('ID1')->nullable();
            $table->integer('ID')->nullable();          // order ID
            $table->string('Action',  200)->nullable();
            $table->string('Color',   100)->nullable();
            $table->float('Qunt_Ac')->nullable();
            $table->float('On')->nullable();
            $table->string('Machin',  100)->nullable();
            $table->float('Hours')->nullable();
            $table->dateTime('Date')->nullable();
            $table->string('NotesA',  500)->nullable();
            $table->float('Kelo')->nullable();
            $table->integer('Year')->nullable();
            $table->float('Actual')->nullable();
            $table->float('Tarkeb')->nullable();
            $table->float('Wash')->nullable();
            $table->float('Electricity')->nullable();
            $table->float('Taghez')->nullable();
            $table->float('StopVar')->nullable();
            $table->string('Tabrer',  500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
