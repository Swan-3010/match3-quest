<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('shop_item_code');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('price_paid');
            $table->string('currency'); // coins, gems
            $table->string('context')->default('general');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('purchases'); }
};