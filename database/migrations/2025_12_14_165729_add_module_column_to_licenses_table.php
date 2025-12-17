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
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('module')->default('global')->after('id')->index();
            // If there was a unique constraint on 'active' status, we should drop it.
            // Assuming we manage active status via application logic or scoped queries.
            $table->index(['status', 'module']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('module');
            $table->dropIndex(['status', 'module']);
        });
    }
};
