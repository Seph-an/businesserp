<?php

namespace Webkul\PluginManager\Console\Commands;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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

        $this->info('Found ' . count($plugins) . ' potential plugins.');

        $installedCount = 0;
        foreach ($plugins as $pluginId => $package) {
            // Skip core plugins as they are handled by erp:install or auto-loaded
            if ($package->isCore) {
                $this->line("Skipping core plugin: <info>{$pluginId}</info>");
                continue;
            }

            $this->info("Installing plugin: <comment>{$pluginId}</comment>...");

            try {
                // Each plugin's :install command handles its own migrations and seeders
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
            $this->newLine();
            $this->info('🛡 Running final permission synchronization...');
            
            $this->call('shield:generate', [
                '--all'    => true,
                '--option' => 'permissions',
                '--panel'  => 'admin',
            ]);

            $role = Role::where('name', Utils::getPanelUserRoleName())->first() ?: Role::first();
            if ($role) {
                $permissions = Permission::all();
                $role->syncPermissions($permissions);
                $this->info("✅ All permissions synchronized to role: <comment>{$role->name}</comment>");
            }

            $this->info("🎉 Bulk plugin installation completed! Installed/Updated {$installedCount} plugins.");
        } else {
            $this->info('No new non-core plugins required installation.');
        }
    }
}
