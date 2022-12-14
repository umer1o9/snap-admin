<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('no_of_allowed_searches')->default(15);
            $table->integer('words')->default(1500);
            $table->integer('time_saved')->default(150);
            $table->boolean('status')->default(1);
            $table->integer('price');
            $table->string('currency')->default('PKR');
            $table->string('code');
            $table->string('description')->nullable();
            $table->string('type')->default('paid'); //Free or Paid for promotional Thing
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('plans');
    }
}
