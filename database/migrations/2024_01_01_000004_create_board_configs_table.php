<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('board_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('width')->default(9);
            $table->unsignedTinyInteger('height')->default(9);
            $table->json('spawn_weights')->nullable();
            $table->json('obstacle_density')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('board_configs'); }
};