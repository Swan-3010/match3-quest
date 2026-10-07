<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('max_level_reached')->default(1);
            $table->unsignedBigInteger('total_score')->default(0);
            $table->unsignedInteger('total_stars')->default(0);
            $table->unsignedTinyInteger('lives')->default(5);
            $table->timestamp('last_life_regen_at')->nullable();
            $table->unsignedInteger('current_xp')->default(0);
            $table->unsignedInteger('xp_for_next_level')->default(5000);
            $table->unsignedInteger('player_level')->default(1);
            $table->unsignedBigInteger('coins')->default(500);
            $table->unsignedBigInteger('gems')->default(10);
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('login_streak')->default(0);
            $table->unsignedInteger('max_login_streak')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('user_progress'); }
};