<?php

namespace App\Http\Middleware\WooCommerce;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates incoming WooCommerce webhook requests against the WC
 * native HMAC signature. WC computes
 *
 *   base64( HMAC_SHA256( raw_body, secret ) )
 *
 * and sends it in the `X-WC-Webhook-Signature` header. The shared
 * secret comes from the WC webhook settings and is mirrored in
 * `WOOCOMMERCE_WEBHOOK_SECRET` on the Laravel side.
 *
 * Using HMAC rather than a raw shared-secret header is the standard
 * WC approach — it ties the signature to the body so replays and
 * tampering can be detected.
 */
class VerifyWebhook
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.woocommerce.webhook_secret');

        if ($secret === '') {
            abort(500, 'Webhook secret is not configured.');
        }

        $provided = (string) $request->header('X-WC-Webhook-Signature', '');

        if ($provided === '') {
            abort(401, 'Missing webhook signature.');
        }

        $expected = base64_encode(
            hash_hmac('sha256', $request->getContent(), $secret, true),
        );

        if (! hash_equals($expected, $provided)) {
            abort(401, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
