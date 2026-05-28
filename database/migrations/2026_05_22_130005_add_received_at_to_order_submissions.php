<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks when an OrderSubmission's goods returned from the vendor and
 * arrived at the warehouse. Setting this triggers Order.packing_ready_at
 * for the parent order, surfacing it in the planning list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_submissions', function (Blueprint $table): void {
            $table->timestamp('received_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_submissions', function (Blueprint $table): void {
            $table->dropColumn('received_at');
        });
    }
};
