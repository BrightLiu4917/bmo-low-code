<?php

namespace BrightLiu\LowCode\Providers;

use Illuminate\Support\ServiceProvider;

class LowCodeServiceProvider extends ServiceProvider
{
    /**
     * 注册服务提供者
     */
    public function register()
    {
        // 合并配置文件
        $this->mergeConfigFrom(
            __DIR__.'/../../config/low-code.php',
            'low-code'
        );
    }

    /**
     * 启动服务提供者
     */
    public function boot()
    {
        $this->publishResources();
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

        $this->publishes([
            __DIR__.'/../../resource/ListSource.php' => app_path('Http/Resources/LowCode'),
        ], 'low-code-list-resource');

        $this->publishes([
            __DIR__.'/../../resource/BasicInfoSource.php' => app_path('Http/Resources/LowCode'),
        ], 'low-code-basic-info-resource');

        // 迁移文件发布
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'low-code-migrations');

        // 可选：同时发布配置和迁移的统一标签
        $this->publishes([
            __DIR__.'/../../config/low-code.php' => config_path('low-code.php'),
            __DIR__.'/../../database/migrations' => database_path('migrations'),
            __DIR__.'/../../resource' => app_path('Http/Resources/LowCode'),
        ], 'low-code-package');
    }
}