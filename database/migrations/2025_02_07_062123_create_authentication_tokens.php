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
        Schema::create('authentication_tokens', function (Blueprint $table) {
            $table->uuid('authentication_token_id')->primary();
            $table->string('user_auth');
            $table->string('token');
            $table->dateTime('expired');

            $table->boolean('soft_delete')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authentication_tokens');
    }
};
