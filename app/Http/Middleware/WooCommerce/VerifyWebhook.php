<?php

namespace App\Http\Middleware\WooCommerce;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates incoming WooCommerce webhook requests against a shared secret
 * sent in the X-Webhook-Secret header.
 */
class VerifyWebhook
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.woocommerce.webhook_secret');

        if ($expected === '') {
            abort(500, 'Webhook secret is not configured.');
        }

        $provided = (string) $request->header('X-Webhook-Secret', '');

        if (! hash_equals($expected, $provided)) {
            abort(401, 'Invalid webhook secret.');
        }

        return $next($request);
    }
}
