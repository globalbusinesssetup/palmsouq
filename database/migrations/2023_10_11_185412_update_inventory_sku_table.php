<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInventorySkuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if the column does not exist before adding it
        if (!Schema::hasColumn('updated_inventories', 'sku')) {
            Schema::table('updated_inventories', function (Blueprint $table) {
                $table->string('sku')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Only drop the column if it exists
        Schema::table('updated_inventories', function (Blueprint $table) {
            if (Schema::hasColumn('updated_inventories', 'sku')) {
                $table->dropColumn('sku');
            }
        });
    }
}
