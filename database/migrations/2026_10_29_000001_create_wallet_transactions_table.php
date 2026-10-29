<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('currency', 32); // coins, gems, lives, booster:<code>
            $table->bigInteger('amount'); // +начисление / −списание
            $table->bigInteger('balance_after')->nullable();
            $table->string('type', 32);
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('client_request_id', 64)->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('currency');
            $table->index('client_request_id');
        });
    }
    public function down(): void { Schema::dropIfExists('wallet_transactions'); }
};
