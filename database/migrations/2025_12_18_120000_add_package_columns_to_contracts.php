<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pool_schema.partner_contracts', function (Blueprint $table) {
            $table->integer('total_sessions')->nullable()->after('billing_frequency');
            $table->integer('remaining_sessions')->nullable()->after('total_sessions');
            $table->integer('attendees_per_session')->nullable()->after('remaining_sessions');
            $table->decimal('package_price', 12, 2)->nullable()->after('attendees_per_session');
            $table->enum('payment_status', ['pending', 'paid'])->default('pending')->after('package_price');
        });

        // Update enum to include fixed_package type
        DB::statement("ALTER TABLE pool_schema.partner_contracts DROP CONSTRAINT IF EXISTS partner_contracts_contract_type_check");
        DB::statement("ALTER TABLE pool_schema.partner_contracts ADD CONSTRAINT partner_contracts_contract_type_check CHECK (contract_type::text = ANY (ARRAY['discount'::text, 'fixed_price'::text, 'flat_fee'::text, 'per_head'::text, 'fixed_package'::text]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pool_schema.partner_contracts', function (Blueprint $table) {
            $table->dropColumn(['total_sessions', 'remaining_sessions', 'attendees_per_session', 'package_price', 'payment_status']);
        });
    }
};
