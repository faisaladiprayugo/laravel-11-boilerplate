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
        Schema::create('user_habit_checklists', function (Blueprint $table) {
            $table->uuid('user_habit_checklist_id')->primary();
            $table->foreignUuid('user_habit_id');
            $table->datetime('datetime');
            $table->integer('status')->default(1);

            $table->boolean('soft_delete')->default(false);
            $table->timestamps();
        });

        Schema::table('user_habit_checklists', function (Blueprint $table) {
            $table->foreign('user_habit_id')->references('user_habit_id')->on('user_habits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_habit_checklists');
    }
};
