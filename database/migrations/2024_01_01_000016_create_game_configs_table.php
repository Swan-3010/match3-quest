<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('game_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_key')->unique();
            $table->json('config_value');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('game_configs'); }
};