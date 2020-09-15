<?php

use App\Models\UpdateRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateSocialMediasAddPolymorphicFields extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('social_medias', function (Blueprint $table) {
            $table->uuid("sociable_id")->after('id')->nullable();
            $table->string("sociable_type")->after('sociable_id')->nullable();
            $table->index(["sociable_type", "sociable_id"]);
        });

        \DB::transaction(function () {
            DB::update('update social_medias set sociable_id = service_id, sociable_type = ? where service_id is not null', [UpdateRequest::EXISTING_TYPE_SERVICE]);
            Schema::table('social_medias', function (Blueprint $table) {
                $table->dropForeign(['service_id']);
                $table->dropColumn('service_id');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::transaction(function () {
            Schema::table('social_medias', function (Blueprint $table) {
                $table->nullableForeignUuid('service_id', 'services');
            });

            DB::update('update social_medias set service_id = sociable_id where sociable_type = ? and sociable_id is not null', [UpdateRequest::EXISTING_TYPE_SERVICE]);
            Schema::table('social_medias', function (Blueprint $table) {
                $table->dropMorphs('sociable');
            });
        });
    }
}
