<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Department snapshot on the OrderLineProduct. Like other snapshot
 * columns (name, is_custom) this captures the classification at order
 * time and is never updated when the source product is re-categorised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_line_products', function (Blueprint $table): void {
            $table->string('department', 32)->nullable()->after('is_custom')->index();
        });
    }

    public function down(): void
    {
        Schema::table('order_line_products', function (Blueprint $table): void {
            $table->dropIndex(['department']);
            $table->dropColumn('department');
        });
    }
};
