<?php

use App\Models\Order;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'App.Models.User.{id}',
    fn (User $user, int $id): bool => $user->id === $id,
);

/**
 * Custom-order submission updates. Only listenable for authenticated
 * staff — we don't expose this app to end customers, so any logged-in
 * user is allowed as long as the order exists. Tighten this when we
 * introduce role-based permissions.
 */
Broadcast::channel(
    'orders.{orderId}.submission',
    fn (User $user, int $orderId): bool => Order::whereKey($orderId)->exists(),
);

/**
 * Per-tour planning channel. Carries TourStatusUpdated / TourOrderAdded
 * / TourOrderRemoved / TourReordered events so admins editing the same
 * tour stay in sync.
 */
Broadcast::channel(
    'tours.{tourId}',
    fn (User $user, int $tourId): bool => Tour::whereKey($tourId)->exists(),
);

/**
 * General tours channel — carries TourStatusUpdated for list-view
 * consumers (planning index and tour-list). Authenticated staff only.
 */
Broadcast::channel(
    'tours',
    fn (User $user): bool => $user !== null,
);

/**
 * General fulfilment channel — carries OrderPackingReady and
 * OrderFlowChanged so any open planning view can refresh its buckets
 * without polling.
 */
Broadcast::channel(
    'fulfillment',
    fn (User $user): bool => $user !== null,
);
