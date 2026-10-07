<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('combo_matrix', function (Blueprint $table) {
            $table->id();
            $table->string('special_a');
            $table->string('special_b');
            $table->json('resulting_effect_json');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['special_a', 'special_b']);
        });
    }
    public function down(): void { Schema::dropIfExists('combo_matrix'); }
};