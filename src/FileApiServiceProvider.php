<?php namespace Unisharp\FileApi;

use Illuminate\Support\ServiceProvider;

/**
 * Class FileapiServiceProvider
 * @package Unisharp\Fileapi
 */
class FileApiServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__ . '/route.php';

        $this->publishes([
            __DIR__ . '/config/fileapi.php' => config_path('fileapi.php', 'config'),
        ], 'fileapi_config');

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

    }
}
