<?php

use Illuminate\Database\Migrations\Migration;

class UpdateServicesAddAdviceToTypesEnum extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement(
            "ALTER TABLE `services` MODIFY COLUMN `type` ENUM('service', 'activity', 'club', 'group', 'helpline', 'information', 'app', 'advice') NOT NULL"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement(
            "ALTER TABLE `services` MODIFY COLUMN `type` ENUM('service', 'activity', 'club', 'group', 'helpline', 'information', 'app') NOT NULL"
        );
    }
}
