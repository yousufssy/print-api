<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [
    'name'            => env('APP_NAME', 'نظام المطبعة'),
    'env'             => env('APP_ENV', 'production'),
    'debug'           => (bool) env('APP_DEBUG', false),
    'url'             => env('APP_URL', 'http://localhost'),
    'asset_url'       => env('ASSET_URL'),
    'timezone'        => 'Asia/Riyadh',
    'locale'          => 'ar',
    'fallback_locale' => 'en',
    'faker_locale'    => 'ar_SA',
    'cipher'          => 'AES-256-CBC',
    'key'             => env('APP_KEY'),
    'previous_keys'   => [...array_filter(explode(',', env('APP_PREVIOUS_KEYS', '')))],
    'maintenance'     => ['driver' => 'file'],

    'providers' => ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),
];
