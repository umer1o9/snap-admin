<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComapniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comapnies', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name');
            $table->string('email');
            $table->string('domain');
            $table->string('no_of_employee')->nullable();
            $table->string('address');
            $table->integer('city_id')->nullable();
            $table->integer('country_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('company_code')->nullable();
            $table->string('about')->nullable();
            $table->boolean('active')->default(1);
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
        Schema::dropIfExists('comapnies');
    }
}
