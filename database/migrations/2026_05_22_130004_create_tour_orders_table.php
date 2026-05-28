<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot connecting an Order to a Tour with its sequence position.
 *
 * Sequence encoding:
 *   - primary_sequence (1, 2, 3, …) — the slot number written on the
 *     physical package by the packers.
 *   - insert_index (0 = original, 1 = A, 2 = B, …) — non-zero when the
 *     order was inserted after the tour was approved. Display is the
 *     primary number with the letter suffix appended (e.g. 2A).
 *   - added_after_approval mirrors `insert_index > 0` but is stored
 *     explicitly so admin queries don't have to encode that rule.
 *
 * Sorting in the UI/API is by `primary_sequence * 100 + insert_index`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('primary_sequence');
            $table->unsignedInteger('insert_index')->default(0);
            $table->boolean('added_after_approval')->default(false);
            $table->timestamps();

            $table->unique(['tour_id', 'order_id']);
            $table->index(['tour_id', 'primary_sequence', 'insert_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_orders');
    }
};
