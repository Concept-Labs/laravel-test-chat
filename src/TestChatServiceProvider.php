<?php

namespace Mtr\TestChat;

use Mtr\TestChat\Console\InstallCommand;
use Illuminate\Support\ServiceProvider;

class TestChatServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/test-chat.php', 'test-chat');
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'test-chat');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/channels.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/test-chat.php' => config_path('test-chat.php'),
        ], 'test-chat-config');

        $this->publishes([
            __DIR__.'/../public' => public_path(config('test-chat.asset_path', 'vendor/test-chat')),
        ], 'test-chat-assets');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'test-chat-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/test-chat'),
        ], 'test-chat-views');

        $this->publishes([
            __DIR__.'/../config/test-chat.php' => config_path('test-chat.php'),
            __DIR__.'/../public' => public_path(config('test-chat.asset_path', 'vendor/test-chat')),
            __DIR__.'/../database/migrations' => database_path('migrations'),
            __DIR__.'/../resources/views' => resource_path('views/vendor/test-chat'),
        ], 'test-chat-install');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }
    }
}