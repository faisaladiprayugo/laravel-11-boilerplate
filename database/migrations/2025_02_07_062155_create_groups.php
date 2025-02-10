<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('group_id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->foreignUuid('owner_id');

            $table->boolean('soft_delete')->default(false);
            $table->timestamps();
        });

        Schema::table('groups', function (Blueprint $table) {
            $table->foreign('owner_id')->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
