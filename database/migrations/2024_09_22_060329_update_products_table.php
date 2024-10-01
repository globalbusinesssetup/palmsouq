<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Add missing columns if they don't already exist
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable();
            }
            if (!Schema::hasColumn('products', 'supplier')) {
                $table->string('supplier')->nullable();
            }
            if (!Schema::hasColumn('products', 'supplier_item_code')) {
                $table->string('supplier_item_code')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Optionally drop the columns when rolling back
            if (Schema::hasColumn('products', 'barcode')) {
                $table->dropColumn('barcode');
            }
            if (Schema::hasColumn('products', 'supplier')) {
                $table->dropColumn('supplier');
            }
            if (Schema::hasColumn('products', 'supplier_item_code')) {
                $table->dropColumn('supplier_item_code');
            }
        });
    }
}
