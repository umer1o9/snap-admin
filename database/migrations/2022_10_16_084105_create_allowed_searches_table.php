<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAllowedSearchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('allowed_searches', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('sale_id');
            $table->boolean('is_closed')->default(0);
            $table->integer('get_section')->default(15);
            $table->integer('get_title')->default(15);
            $table->integer('expend_blogpost')->default(15);
            $table->integer('video_script')->default(15);
            $table->integer('linkedin_post')->default(15);
            $table->integer('sales_copies')->default(15);
            $table->integer('improve_headline')->default(15);
            $table->integer('suggest_headline')->default(15);
            $table->integer('brain_stormer')->default(15);
            $table->integer('action_item')->default(15);
            $table->integer('easy_to_read')->default(15);
            $table->integer('professional_talk')->default(15);
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
        Schema::dropIfExists('allowed_searches');
    }
}
