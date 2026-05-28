<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A delivery tour groups orders that will be self-delivered together on
 * a given day (Delivery flow only — Shipping and Pickup don't belong on
 * tours).
 *
 * Status lifecycle: draft → approved → completed.
 * - draft:     freely re-sortable, hidden from departments.
 * - approved:  sequence locked, visible to packing app, inserts get an
 *              A/B/C suffix to preserve the printed labels.
 * - completed: all orders dispatched. Effectively read-only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('tour_date')->index();
            $table->string('status', 16)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
