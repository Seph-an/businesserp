<?php

namespace Webkul\PluginManager\Console\Commands;

use Illuminate\Console\Command;
use Webkul\PluginManager\Models\Plugin;

class InstallAllPlugins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugins:install {--force : Force installation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all available non-core plugins in the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting bulk plugin installation...');

        $plugins = Plugin::getAllPluginPackages();

        if (empty($plugins)) {
            $this->warn('No plugins found to install.');

            return;
        }

        $installedCount = 0;
        foreach ($plugins as $pluginId => $package) {
            // Skip core plugins as they are handled by erp:install or auto-loaded
            if ($package->isCore) {
                continue;
            }

            $this->info("Installing plugin: <comment>{$pluginId}</comment>...");

            try {
                // Each plugin's :install command handles its own migrations and seeders
                // as defined in its ServiceProvider (e.g., runsMigrations(), runsSeeders())
                $this->call("{$pluginId}:install", [
                    '--force'          => $this->option('force'),
                    '--no-interaction' => true,
                ]);

                $installedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to install plugin '{$pluginId}': {$e->getMessage()}");
            }
        }

        if ($installedCount > 0) {
            $this->info("🎉 Bulk plugin installation completed! Installed/Updated {$installedCount} plugins.");
        } else {
            $this->info('No new non-core plugins required installation.');
        }
    }
}
