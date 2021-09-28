<?php

namespace Albawebstudio\AppleSecretGenerator\Providers;

use Albawebstudio\AppleSecretGenerator\Console\AppleSecretGeneratorCommand;
use Illuminate\Support\ServiceProvider;

class LumenServiceProvider extends ServiceProvider
{

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->app->configure('apple');

        $path = realpath(__DIR__.'/../../config/config.php');
        $this->mergeConfigFrom($path, 'apple');
    }
    /**
     * Register
     */
    public function register()
    {
        $this->registerAppleSecretGeneratorCommand();

        $this->commands('albawebstudio.apple.secret');
    }

    protected function registerAppleSecretGeneratorCommand()
    {
        $this->app->singleton('albawebstudio.apple.secret', function () {
            return new AppleSecretGeneratorCommand();
        });
    }

    protected function config($key, $default = null)
    {
        return config("apple.$key", $default);
    }
}