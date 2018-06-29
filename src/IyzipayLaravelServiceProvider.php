<?php

namespace Iyzico\IyzipayLaravel;

use Iyzico\IyzipayLaravel\Commands\SubscriptionChargeCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class IyzipayLaravelServiceProvider extends ServiceProvider
{

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('iyzipay.php')
        ]);

        if (! class_exists('AddBillableFields')) {
            $this->publishes([
                __DIR__.'/../database/migrations/add_billable_fields.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_add_billable_fields.php'),
            ], 'migrations');
        }

        if (! class_exists('CreateCreditCardsTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_credit_cards_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_credit_cards_table.php'),
            ], 'migrations');
        }

        if (! class_exists('CreateSubscriptionsTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_subscriptions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_subscriptions_table.php'),
            ], 'migrations');
        }

        if (! class_exists('CreateTransactionsTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_transactions_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_transactions_table.php'),
            ], 'migrations');
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('iyzipay:subscription_charge')->daily();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                SubscriptionChargeCommand::class
            ]);
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/config.php',
            'iyzipay'
        );

        $this->app->bind('iyzipay-laravel', function () {
            return new IyzipayLaravel();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['iyzipay-laravel'];
    }
}
