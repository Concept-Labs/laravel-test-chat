<?php

namespace Mtr\TestChat\Console;

use Illuminate\Console\Command;
use Mtr\TestChat\Database\Seeders\TestChatUsersSeeder;

class InstallCommand extends Command
{
    protected $signature = 'test-chat:install {--force : Overwrite published files} {--seed : Seed demo chat users}';

    protected $description = 'Install and configure mtr/test-chat package assets and migrations';

    /**
     * {@inheritdoc}
     */
    public function handle(): int
    {
        $this->info('Installing mtr/test-chat package...');

        $publishArgs = [
            '--tag' => 'test-chat-install',
            '--force' => (bool) $this->option('force'),
        ];

        $this->call('vendor:publish', $publishArgs);
        $this->call('migrate', ['--force' => true]);

        if ((bool) $this->option('seed')) {
            $this->call('db:seed', [
                '--class' => TestChatUsersSeeder::class,
                '--force' => true,
            ]);
        }

        if (! file_exists(config_path('broadcasting.php'))) {
            $this->warn('broadcasting.php not found. Run: php artisan install:broadcasting');
        }

        $this->newLine();
        $this->info('mtr/test-chat installed successfully.');
        $this->line('Next: ensure BROADCAST_CONNECTION=reverb + Redis in .env, then run: php artisan reverb:start');
        $this->line('Optional: php artisan test-chat:install --seed');

        return self::SUCCESS;
    }
}