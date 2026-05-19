<?php

use App\Models\Order;
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
