<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-order shipping snapshot. Captured at sync time from WC's
 * shipping_lines payload + the `_pft_rate_id` meta added by the
 * WP-side mu-plugin. One row per order — in the rare case WC sends
 * multiple shipping lines (only a handful of historical orders) we
 * keep only the highest-cost line (real shipping wins over
 * admin-instruction "fake" lines).
 *
 * `category` is derived by ShippingCategorizerService at write time
 * from rate_id → instance_id → method_id (in that fallback order).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->string('category', 32)->default('unknown');
            $table->unsignedInteger('rate_id')->nullable();
            $table->string('method_id');
            $table->unsignedInteger('instance_id')->nullable();
            $table->string('method_title');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();

            $table->unique('order_id');
            $table->index('category');
            $table->index('rate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipments');
    }
};
