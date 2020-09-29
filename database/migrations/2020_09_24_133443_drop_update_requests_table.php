<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUpdateRequestsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::dropIfExists('update_requests');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::create('update_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id', 'users');
            $table->morphsUuid('updateable');
            $table->json('data');
            $table->timestamps();
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
        });
    }
}
