<?php

namespace Illuminate\Encryption;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Opis\Closure\SerializableClosure;

class EncryptionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEncrypter();
        $this->registerOpisSecurityKey();
    }

    /**
     * Register the encrypter.
     *
     * @return void
     */
    protected function registerEncrypter()
    {
        $this->app->singleton('encrypter', function ($app) {
            $config = $app->make('config')->get('app');

            return (new Encrypter($this->parseKey($config), $config['cipher']))
                            ->additionalDecryptionKeys(array_map(function ($key) {
                                return $this->parseKeyString($key);
                            }, $config['previous_keys'] ?? []));
        });
    }

    /**
     * Configure Opis Closure signing for security.
     *
     * @return void
     */
    protected function registerOpisSecurityKey()
    {
        $config = $this->app->make('config')->get('app');

        if (! class_exists(SerializableClosure::class) || empty($config['key'])) {
            return;
        }

        SerializableClosure::setSecretKey($this->parseKey($config));
    }

    /**
     * Parse the encryption key from the configuration array.
     *
     * @param  array  $config
     * @return string
     */
    protected function parseKey(array $config)
    {
        return $this->parseKeyString($this->key($config));
    }

    /**
     * Parse the encryption key string.
     *
     * @param  string  $key
     * @return string
     */
    protected function parseKeyString(string $key)
    {
        return Str::startsWith($key, $prefix = 'base64:')
                    ? base64_decode(Str::after($key, $prefix))
                    : $key;
    }

    /**
     * Extract the encryption key from the given configuration.
     *
     * @param  array  $config
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function key(array $config)
    {
        return tap($config['key'], function ($key) {
            if (empty($key)) {
                throw new MissingAppKeyException;
            }
        });
    }
}
