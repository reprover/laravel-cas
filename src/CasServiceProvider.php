<?php

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Reprover\LaravelCas\CasUserProvider;

/**
 * @name CasServiceProvider
 */
class CasServiceProvider extends ServiceProvider
{

    protected $defer = true;

    public function boot()
    {
        $config = __DIR__."/config/cas.php";

        $this->publishes([
            $config => config_path("cas.php"),
        ]);


        $auth = Auth::getFacadeRoot();

        if (method_exists($auth, 'provider')) {
            $auth->provider('cas', function ($app, array $config) {
                return $this->makeUserProvider($app['hash'], $config);
            });
        } else {
            $auth->extend('cas', function ($app) {
                return $this->makeUserProvider($app['hash'], $app['config']['auth']);
            });
        }

        $this->commands(Import::class);

    }

    public function register()
    {

    }


    /**
     * Returns a new Adldap user provider.
     *
     * @param Hasher $hasher
     * @param array  $config
     *
     * @throws \RuntimeException
     *
     * @return \Illuminate\Contracts\Auth\UserProvider
     */
    protected function makeUserProvider(Hasher $hasher, array $config)
    {
        $provider = Config::get('cas.provider', CasUserProvider::class);

        // The DatabaseUserProvider requires a model to be configured
        // in the configuration. We'll validate this here.
        if (is_a($provider, CasUserProvider::class, $allowString = true)) {
            $model = array_get($config, 'model');

            if (!$model) {
                throw new \RuntimeException(
                    "No model is configured. You must configure a model to use with the {$provider}."
                );
            }

            return new $provider($hasher, $model);
        }

        return new $provider;
    }

}