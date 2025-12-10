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
        DB::statement("
            CREATE TABLE IF NOT EXISTS pool_schema.product_images (
                id BIGSERIAL PRIMARY KEY,
                product_id BIGINT NOT NULL,
                image_path VARCHAR(255) NOT NULL,
                is_primary BOOLEAN NOT NULL DEFAULT FALSE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                CONSTRAINT product_images_product_id_foreign FOREIGN KEY (product_id) REFERENCES pool_schema.products (id) ON DELETE CASCADE
            )
        ");

        $hasColumn = DB::select("SELECT column_name FROM information_schema.columns WHERE table_schema = 'pool_schema' AND table_name = 'products' AND column_name = 'purchase_price'");
        if (empty($hasColumn)) {
            DB::statement("ALTER TABLE pool_schema.products ADD COLUMN purchase_price DECIMAL(8, 2) NOT NULL DEFAULT 0");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE pool_schema.products DROP COLUMN IF EXISTS purchase_price");
        DB::statement("DROP TABLE IF EXISTS pool_schema.product_images");
    }
};
