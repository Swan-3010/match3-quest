<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
            $table->index(['event_type', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('analytics_events'); }
};