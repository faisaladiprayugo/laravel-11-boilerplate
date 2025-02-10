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
        Schema::create('group_members', function (Blueprint $table) {
            $table->uuid('group_member_id')->primary();
            $table->foreignUuid('group_id');
            $table->foreignUuid('user_id');
            $table->integer('role')->default(2);

            $table->boolean('soft_delete')->default(false);
            $table->timestamps();
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->foreign('group_id')->references('group_id')->on('groups');
            $table->foreign('user_id')->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
