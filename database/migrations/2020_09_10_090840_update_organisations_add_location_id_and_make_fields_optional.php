<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrganisationsAddLocationIdAndMakeFieldsOptional extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->string('url')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->change();
            $table->nullableForeignUuid('location_id', 'locations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->string('url');
            $table->string('email');
            $table->string('phone');
        });

        Schema::table('organisations', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
        });
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn('location_id');
        });
    }
}
