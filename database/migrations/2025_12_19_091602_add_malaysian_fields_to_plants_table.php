<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->string('malay_name', 100)->nullable()->after('common_name');
            $table->boolean('is_endemic')->default(false)->after('lifespan');
            $table->boolean('is_native')->default(true)->after('is_endemic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn(['malay_name', 'is_endemic', 'is_native']);
        });
    }
};
