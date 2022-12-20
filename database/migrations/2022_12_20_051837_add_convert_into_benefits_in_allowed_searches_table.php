<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConvertIntoBenefitsInAllowedSearchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('allowed_searches', function (Blueprint $table) {
            $table->integer('convert_into_benefits')->default(15);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('allowed_searches', function (Blueprint $table) {
            //
            $table->dropColumn('convert_into_benefits');
        });
    }
}
