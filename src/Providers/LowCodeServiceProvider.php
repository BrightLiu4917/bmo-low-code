<?php

namespace BrightLiu\LowCode\Providers;

use Illuminate\Support\ServiceProvider;
use BrightLiu\LowCode\Context\OrgContext;
use BrightLiu\LowCode\Context\AuthContext;
use BrightLiu\LowCode\Context\AdminContext;
use BrightLiu\LowCode\Context\DiseaseContext;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class LowCodeServiceProvider extends ServiceProvider
{
    /**
     * 注册服务提供者
     */
    public function register()
    {
        $this->app->singleton('context:org', OrgContext::class);

        $this->app->singleton('context:auth', AuthContext::class);

        $this->app->singleton('context:disease', DiseaseContext::class);

        $this->app->singleton('context:admin', AdminContext::class);

        // 合并配置文件
        $this->mergeConfigFrom(
            __DIR__.'/../../config/low-code.php',
            'low-code'
        );

        $this->loadDependencies();
    }

    /**
     * 启动服务提供者
     */
    public function boot()
    {
        $this->publishResources();

        $this->registerModuleRoutes();
    }

    /**
     * 发布所有资源
     */
    protected function publishResources()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        // 配置文件发布
        $this->publishes([
            __DIR__.'/../../config/low-code.php' => config_path('low-code.php'),
        ], 'low-code-config');

        // 配置文件发布
        $this->publishes([
            __DIR__.'/../../config/business/bmo-service.php' => config_path('business/bmo-service.php'),
        ], 'bmo-service-config');

//        $this->publishes([
//            __DIR__.'/../../resource/ListSource.php' => app_path('Http/Resources/LowCode'),
//        ], 'low-code-list-resource');
//
//        $this->publishes([
//            __DIR__.'/../../resource/BasicInfoSource.php' => app_path('Http/Resources/LowCode'),
//        ], 'low-code-basic-info-resource');

        // 迁移文件发布
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'low-code-migrations');

        // 可选：同时发布配置和迁移的统一标签
        $this->publishes([
            __DIR__.'/../../config/low-code.php' => config_path('low-code.php'),
            __DIR__.'/../../config/business/bmo-service.php' => config_path('business/bmo-service.php'),
            __DIR__.'/../../database/migrations' => database_path('migrations'),
//            __DIR__.'/../../resource' => app_path('Http/Resources/LowCode'),
        ], 'low-code-package');
    }

    /**
     * 注册模块路由收集
     */
    protected function registerModuleRoutes(): void
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        $rootPath = __DIR__ . '/../../route';

        transform(
            config('low-code.http.modules', []),
            function ($modules) use ($rootPath) {
                foreach ($modules as $moduleName => $options) {
                    Route::group(
                        $options,
                        array_map(
                            fn ($file) => "{$rootPath}/{$file}",
                            Storage::build($rootPath)->files($moduleName, true)
                        )
                    );
                }
            }
        );
    }

    protected function loadDependencies(): void
    {
        $dependencies = (array) config('low-code.dependencies', []);

        foreach ($dependencies as $source => $dependency) {
            if ($source === $dependency) {
                continue;
            }

            $this->app->alias($dependency, $source);
        }
    }
}