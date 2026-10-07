<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('level_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained('levels')->onDelete('cascade');
            $table->unsignedTinyInteger('threshold_type'); // 1=target, 2=star2, 3=star3
            $table->string('reward_code');
            $table->unsignedInteger('reward_qty')->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('level_rewards'); }
};