<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('meetup_id')->constrained('meetups')->cascadeOnDelete();
            $table->string('status');
            $table->timestamps();
            $table->unique(['user_id', 'meetup_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
