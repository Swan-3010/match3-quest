<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('unlock_level');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('unlock_condition');
            $table->json('effect_json');
            $table->string('board_impact')->default('none');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('features'); }
};