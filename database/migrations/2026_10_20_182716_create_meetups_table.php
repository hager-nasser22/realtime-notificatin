<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->index();
            $table->string('location');
            $table->unsignedInteger('capacity')->default(0);
            $table->boolean('requires_approval')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetups');
    }
};
