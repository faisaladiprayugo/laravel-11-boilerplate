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
        Schema::create('user_habits', function (Blueprint $table) {
            $table->uuid('user_habit_id')->primary();
            $table->foreignUuid('user_id');
            $table->foreignUuid('group_id')->nullable();
            $table->string('name');
            $table->integer('schedule_type')->default(1)->comment("1:Daily, 2:Weekly, 3:Monthly, 4:Yearly, 5:Custom");
            $table->string('schedule')->nullable();

            $table->boolean('soft_delete')->default(false);
            $table->timestamps();
        });

        Schema::table('user_habits', function (Blueprint $table) {
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('group_id')->references('group_id')->on('groups');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_habits');
    }
};
