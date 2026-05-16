<?php

namespace App\Providers;

use App\Support\WooCommerce\WooCommerceClient;
use Illuminate\Support\ServiceProvider;

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
        //
    }
}
