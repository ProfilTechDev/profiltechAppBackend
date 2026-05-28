<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the two planning fields the new fulfilment domain reads from
 * the orders table:
 *
 *   - delivery_flow    Admin-set bucket (delivery / shipping / pickup).
 *                      Defaulted from OrderShipment.category at sync,
 *                      mutable from the planning UI. Nullable while no
 *                      decision has been made.
 *   - packing_ready_at Timestamp at which the order is allowed to enter
 *                      the planning list. Set automatically at sync for
 *                      non-custom orders; for custom orders it stays
 *                      null until admin marks the OrderSubmission as
 *                      received from the vendor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_flow', 32)->nullable()->after('status')->index();
            $table->timestamp('packing_ready_at')->nullable()->after('delivery_flow')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['delivery_flow']);
            $table->dropIndex(['packing_ready_at']);
            $table->dropColumn(['delivery_flow', 'packing_ready_at']);
        });
    }
};
