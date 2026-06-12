<?php

namespace Webkul\PluginManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use ReflectionClass;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class Plugin extends Model implements Sortable
{
    use SortableTrait;

    protected $fillable = [
        'name',
        'author',
        'summary',
        'description',
        'latest_version',
        'license',
        'is_active',
        'is_installed',
        'sort',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Plugin::class,
            'plugin_dependencies',
            'plugin_id',
            'dependency_id'
        );
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Plugin::class,
            'plugin_dependencies',
            'dependency_id',
            'plugin_id'
        );
    }

    protected static function getAllPluginPackages(): array
    {
        $packages = [];

        // First, try to get packages from registered Filament plugins
        $panels = [];
        try {
            $panels = app('filament')->getPanels();
        } catch (\Throwable $e) {
            // Filament might not be ready yet during some CLI commands
        }

        foreach ($panels as $panel) {
            foreach ($panel->getPlugins() as $pluginId => $plugin) {
                $pluginClass = get_class($plugin);
                $serviceProviderClass = str_replace('Plugin', 'ServiceProvider', $pluginClass);

                if (! class_exists($serviceProviderClass)) {
                    continue;
                }

                $reflection = new ReflectionClass($serviceProviderClass);
                if (! $reflection->isSubclassOf(PackageServiceProvider::class)) {
                    continue;
                }

                $serviceProvider = new $serviceProviderClass(app());
                $package = new Package;
                $serviceProvider->configureCustomPackage($package);

                if ($package->isCore) {
                    continue;
                }

                $package->basePath = dirname($reflection->getFileName(), 2);
                $packages[$pluginId] = $package;
            }
        }

        // Second, scan the filesystem to find plugins that might not be registered yet
        $pluginPath = base_path('plugins/webkul');
        if (is_dir($pluginPath)) {
            $directories = array_diff(scandir($pluginPath), ['.', '..']);
            
            foreach ($directories as $dir) {
                $composerPath = "$pluginPath/$dir/composer.json";
                if (! file_exists($composerPath)) {
                    continue;
                }

                $composer = json_decode(file_get_contents($composerPath), true);
                $providers = data_get($composer, 'extra.laravel.providers', []);
                
                foreach ($providers as $provider) {
                    if (! class_exists($provider)) {
                        continue;
                    }

                    $reflection = new ReflectionClass($provider);
                    if (! $reflection->isSubclassOf(PackageServiceProvider::class)) {
                        continue;
                    }

                    $serviceProvider = new $provider(app());
                    $package = new Package;
                    $serviceProvider->configureCustomPackage($package);

                    if ($package->isCore) {
                        continue;
                    }

                    $pluginId = $package->name;
                    if (! isset($packages[$pluginId])) {
                        $package->basePath = dirname($reflection->getFileName(), 2);
                        $packages[$pluginId] = $package;
                    }
                }
            }
        }

        return $packages;
    }

    public function getPackageAttribute(): ?Package
    {
        $packages = static::getAllPluginPackages();

        return $packages[$this->name] ?? null;
    }

    public function getDependenciesFromConfig(): array
    {
        return $this->package?->dependencies ?? [];
    }

    public function getDependentsFromConfig(): array
    {
        $packages = static::getAllPluginPackages();

        $dependents = [];

        foreach ($packages as $pluginName => $package) {
            if ($pluginName === $this->name) {
                continue;
            }

            if (! in_array($this->name, $package->dependencies)) {
                continue;
            }

            $dependents[] = $pluginName;
        }

        return $dependents;
    }
}
