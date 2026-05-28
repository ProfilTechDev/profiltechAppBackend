<?php

namespace App\Services;

use App\Enums\DeliveryFlow;
use App\Enums\TourStatus;
use App\Events\Tours\TourOrderAdded;
use App\Events\Tours\TourOrderRemoved;
use App\Events\Tours\TourReordered;
use App\Events\Tours\TourStatusUpdated;
use App\Models\Order;
use App\Models\Tour;
use App\Models\TourOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Domain logic for delivery tours: CRUD, sequence assignment, and the
 * draft → approved → completed lifecycle. The approve gate is what
 * makes packing slips visible to the departments downstream, so all
 * sequence mutations after approval go through the insert-with-suffix
 * path here instead of renumbering.
 */
class TourService
{
    public function createTour(string $name, CarbonImmutable $tourDate, ?string $notes = null): Tour
    {
        return Tour::create([
            'name' => $name,
            'tour_date' => $tourDate,
            'status' => TourStatus::Draft,
            'notes' => $notes,
        ]);
    }

    /**
     * Update the editable metadata on a tour. Status transitions go
     * through the dedicated methods (`approveTour`, `completeTour`) so
     * the corresponding events fire — they're filtered out here even
     * if a caller passes them.
     *
     * @param  array<string, mixed>  $fields
     */
    public function updateTour(Tour $tour, array $fields): Tour
    {
        $allowed = array_intersect_key($fields, array_flip(['name', 'tour_date', 'notes']));

        if ($allowed !== []) {
            $tour->update($allowed);
        }

        return $tour;
    }

    /**
     * Delete a tour. Only allowed while still a draft so we don't drop
     * a paper-printed sequence and confuse the packing floor.
     */
    public function deleteTour(Tour $tour): void
    {
        if ($tour->status !== TourStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only draft tours can be deleted.',
            ]);
        }

        $tour->delete();
    }

    /**
     * Place an order on the tour and assign it a sequence slot.
     *
     * Behaviour depends on the tour's status:
     *   - Draft: appends at the end with the next free primary slot.
     *     Both `$afterPrimary` and `$atPrimary` are ignored — use
     *     `reorderTour()` for sorting drafts.
     *   - Approved with `$atPrimary` specified: fills an empty slot
     *     (typically a gap left by a removed order). Validated that
     *     the slot is actually empty. Useful when 1,2,3,4 had 1,2
     *     removed and a new order should reclaim "1".
     *   - Approved with `$afterPrimary` specified: inserts between
     *     two existing slots, taking the same primary_sequence as the
     *     anchor and the next free letter suffix (2A, 2B …). This is
     *     where A/B/C matters: the new package slots in between
     *     already-printed labels without renumbering them.
     *   - Approved with neither: appends at the end with the next
     *     free primary slot — A/B/C is reserved for between-slot
     *     inserts.
     *   - Completed: rejected.
     *
     * Non-Delivery orders (Shipping / Pickup) are auto-promoted to
     * Delivery — admin chose to add them to the route on purpose (e.g.
     * the truck happens to pass near a Shipping customer). The flow
     * switch is part of the same transaction and dispatches the usual
     * OrderFlowChanged event so other UIs stay in sync.
     *
     * Orders already on another tour are rejected — moving between
     * tours is a remove-then-add operation by design.
     */
    public function addOrderToTour(
        Tour $tour,
        Order $order,
        ?int $afterPrimary = null,
        ?int $atPrimary = null,
    ): TourOrder {
        if ($tour->status === TourStatus::Completed) {
            throw ValidationException::withMessages([
                'status' => 'Cannot add orders to a completed tour.',
            ]);
        }

        if (TourOrder::where('order_id', $order->id)->exists()) {
            throw ValidationException::withMessages([
                'order' => 'Order is already on a tour. Remove it from that tour first.',
            ]);
        }

        if ($atPrimary !== null && $tour->tourOrders()->where('primary_sequence', $atPrimary)->exists()) {
            throw ValidationException::withMessages([
                'at_primary' => "Slot {$atPrimary} is already occupied — pick an empty slot or use after_primary for an A/B/C insert.",
            ]);
        }

        $pivot = DB::transaction(function () use ($tour, $order, $afterPrimary, $atPrimary): TourOrder {
            // Promote the order to Delivery via FulfillmentService so the
            // transition stays in one place (DB update + event dispatch
            // + future invariants). Resolved lazily because
            // FulfillmentService already injects TourService — eager
            // constructor injection would create a circular dependency.
            // The nested DB::transaction in setDeliveryFlow becomes a
            // savepoint of this outer transaction, preserving atomicity.
            app(FulfillmentService::class)->setDeliveryFlow($order, DeliveryFlow::Delivery);

            if ($tour->status === TourStatus::Approved && $atPrimary !== null) {
                return $tour->tourOrders()->create([
                    'order_id' => $order->id,
                    'primary_sequence' => $atPrimary,
                    'insert_index' => 0,
                    'added_after_approval' => true,
                ]);
            }

            if ($tour->status === TourStatus::Draft || $afterPrimary === null) {
                $nextPrimary = ((int) $tour->tourOrders()->max('primary_sequence')) + 1;

                return $tour->tourOrders()->create([
                    'order_id' => $order->id,
                    'primary_sequence' => $nextPrimary,
                    'insert_index' => 0,
                    'added_after_approval' => $tour->status === TourStatus::Approved,
                ]);
            }

            $nextInsertIndex = ((int) $tour->tourOrders()
                ->where('primary_sequence', $afterPrimary)
                ->max('insert_index')) + 1;

            return $tour->tourOrders()->create([
                'order_id' => $order->id,
                'primary_sequence' => $afterPrimary,
                'insert_index' => $nextInsertIndex,
                'added_after_approval' => true,
            ]);
        });

        TourOrderAdded::dispatch($tour, $pivot);

        return $pivot;
    }

    /**
     * Remove an order from a tour. Sequence is NOT renumbered — the
     * physical packages have the original number written on them, and
     * a gap (e.g. 1, 3, 4) is less confusing than a renumber that
     * invalidates labels.
     */
    public function removeOrderFromTour(Tour $tour, Order $order): void
    {
        if ($tour->status === TourStatus::Completed) {
            throw ValidationException::withMessages([
                'status' => 'Cannot remove orders from a completed tour.',
            ]);
        }

        $pivot = $tour->tourOrders()->where('order_id', $order->id)->first();

        if ($pivot === null) {
            return;
        }

        $pivot->delete();

        TourOrderRemoved::dispatch($tour, $order);
    }

    /**
     * Re-sequence a draft tour from a list of order ids in the desired
     * order. Renumbers from 1; insert_index is reset to 0. Approved
     * tours cannot be reordered — their packages already carry the
     * printed sequence.
     *
     * @param  array<int, int>  $orderedOrderIds
     */
    public function reorderTour(Tour $tour, array $orderedOrderIds): void
    {
        if ($tour->status !== TourStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only draft tours can be reordered. Insert orders individually instead.',
            ]);
        }

        DB::transaction(function () use ($tour, $orderedOrderIds): void {
            foreach ($orderedOrderIds as $position => $orderId) {
                $tour->tourOrders()
                    ->where('order_id', $orderId)
                    ->update([
                        'primary_sequence' => $position + 1,
                        'insert_index' => 0,
                    ]);
            }
        });

        TourReordered::dispatch($tour);
    }

    /**
     * Approve a draft tour. Locks the sequence, surfaces the orders to
     * the downstream packing departments, and forbids re-sorting (only
     * A/B/C inserts are allowed past this point).
     */
    public function approveTour(Tour $tour): Tour
    {
        if ($tour->status !== TourStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only draft tours can be approved.',
            ]);
        }

        $this->transitionTo($tour, TourStatus::Approved, ['approved_at' => now()]);

        return $tour;
    }

    /**
     * Mark a tour as completed. Used once every order on the tour has
     * been packed and dispatched. After this point the tour is
     * effectively read-only.
     */
    public function completeTour(Tour $tour): Tour
    {
        if ($tour->status !== TourStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => 'Only approved tours can be completed.',
            ]);
        }

        $this->transitionTo($tour, TourStatus::Completed, ['completed_at' => now()]);

        return $tour;
    }

    /**
     * Apply a status change + any extra columns in a single update,
     * then dispatch `TourStatusUpdated` so listeners (broadcast, audit)
     * can react. Mirrors the pattern in CustomOrderService.
     *
     * @param  array<string, mixed>  $extra
     */
    private function transitionTo(
        Tour $tour,
        TourStatus $newStatus,
        array $extra = [],
        ?string $reason = null,
    ): void {
        $previous = $tour->status;

        $tour->update(['status' => $newStatus, ...$extra]);

        TourStatusUpdated::dispatch($tour, $previous, $reason);
    }
}
