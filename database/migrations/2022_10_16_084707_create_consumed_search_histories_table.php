<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsumedSearchHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consumed_search_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('allowed_search_id'); //Table allowed Searches
            $table->integer('widget_id'); //Special Table for Widgets
            $table->integer('pending_searches'); //Count of All pending Searches related current Widget
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
        Schema::dropIfExists('consumed_search_histories');
    }
}
