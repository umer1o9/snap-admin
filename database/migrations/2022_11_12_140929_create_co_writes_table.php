<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoWritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('co_writes', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('parent_id')->nullable(); //count be same table's ID
            $table->longText('section_to_expend')->nullable();
            $table->string('keywords')->nullable();
            $table->string('creativity')->nullable();
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
        Schema::dropIfExists('co_writes');
    }
}
