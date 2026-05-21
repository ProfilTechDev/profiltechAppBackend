<?php

namespace App\Providers;

use App\Support\WooCommerce\WooCommerceClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WooCommerceClient::class, function ($app): WooCommerceClient {
            $config = $app['config']->get('services.woocommerce');

            return new WooCommerceClient(
                baseUrl: (string) ($config['base_url'] ?? ''),
                consumerKey: (string) ($config['consumer_key'] ?? ''),
                consumerSecret: (string) ($config['consumer_secret'] ?? ''),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Password policy used by Password::defaults() across the app
        // (admin reset, invitation accept, future Fortify resets). Keep
        // the criteria in sync with app/utils/password-policy.ts on the
        // frontend — that's what the live strength meter validates.
        Password::defaults(fn () => Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols(),
        );
    }
}
