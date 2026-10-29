<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('client_request_id', 64)->nullable()->after('currency');
            $table->index('client_request_id');
        });
    }
    public function down(): void {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['client_request_id']);
            $table->dropColumn('client_request_id');
        });
    }
};
