<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoScriptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('video_scripts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->text('product_description')->nullable();
            $table->string('mood')->nullable();
            $table->string('structure')->nullable();
            $table->longtext('request')->nullable();
            $table->longtext('response')->nullable();
            $table->boolean('valid')->default(0);
            $table->boolean('is_public')->default(0);
            $table->string('public_url')->nullable();
            $table->boolean('favorite')->default(0);
            $table->softDeletes();
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
        Schema::dropIfExists('video_scripts');
    }
}
