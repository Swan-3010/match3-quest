<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sessions_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('level_id');
            $table->unsignedInteger('moves_used');
            $table->unsignedInteger('score');
            $table->unsignedTinyInteger('stars_earned')->default(0);
            $table->enum('result', ['win', 'fail', 'quit']);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->json('moves_log')->nullable();
            $table->string('client_hash')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['level_id', 'result']);
        });
    }
    public function down(): void { Schema::dropIfExists('sessions_log'); }
};