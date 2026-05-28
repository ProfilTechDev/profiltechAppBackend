<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-order packing-pipeline state. Valid values depend on the order's
 * delivery_flow — see PackingStatus::allowedFor(). Nullable while
 * delivery_flow hasn't been chosen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('packing_status', 32)->nullable()->after('delivery_flow')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['packing_status']);
            $table->dropColumn('packing_status');
        });
    }
};
