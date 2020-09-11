<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSocialMediasAddPolymorphicFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('social_medias', function (Blueprint $table) {
            $table->nullableUuidMorphs('sociable');
        });

        if (Schema::hasColumn('social_medias', 'service_id')) {
            \DB::transaction(function () {
                DB::update('update social_medias set sociable_id = service_id, sociable_type = ? where service_id is not null', [\App\Models\Service::class]);
                Schema::table('social_medias', function (Blueprint $table) {
                    $table->dropForeign(['service_id']);
                    $table->dropColumn('service_id');
                });
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('social_medias', 'sociable_id')) {
            DB::transaction(function () {
                Schema::table('social_medias', function (Blueprint $table) {
                    $table->nullableForeignUuid('service_id', 'services');
                });

                DB::update('update social_medias set service_id = sociable_id where sociable_type = ? and sociable_id is not null', [\App\Models\Service::class]);
                Schema::table('social_medias', function (Blueprint $table) {
                    $table->dropMorphs('sociable');
                });
            });
        }
    }
}
