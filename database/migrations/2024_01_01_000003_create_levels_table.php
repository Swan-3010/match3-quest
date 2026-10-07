<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('level_number')->unique();
            $table->unsignedInteger('moves_limit')->default(23);
            $table->unsignedInteger('target_score')->default(1500);
            $table->unsignedInteger('star_2_score')->default(1950);
            $table->unsignedInteger('star_3_score')->default(2400);
            $table->json('obstacles_config')->nullable();
            $table->unsignedTinyInteger('colors_count')->default(5);
            $table->string('special_unlocked')->nullable();
            $table->unsignedInteger('config_version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('levels'); }
};