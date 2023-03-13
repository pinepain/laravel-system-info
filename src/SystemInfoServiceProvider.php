<?php declare(strict_types=1);

namespace Pinepain\SystemInfo;


use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Pinepain\SystemInfo\Checkers\CacheStatusChecker;
use Pinepain\SystemInfo\Checkers\DatabasesStatusChecker;
use Pinepain\SystemInfo\Checkers\RedisStatusChecker;
use Pinepain\SystemInfo\Checkers\StatusChecker;
use Pinepain\SystemInfo\Console\GenTokenCommand;
use Pinepain\SystemInfo\Console\PingCommand;
use Pinepain\SystemInfo\Console\StatusCommand;
use Pinepain\SystemInfo\Console\VersionCommand;
use Pinepain\SystemInfo\Http\Controllers\EchoController;
use Pinepain\SystemInfo\Http\Controllers\PingController;
use Pinepain\SystemInfo\Http\Controllers\RequestController;
use Pinepain\SystemInfo\Http\Controllers\ServerController;
use Pinepain\SystemInfo\Http\Controllers\StatusController;
use Pinepain\SystemInfo\Http\Controllers\TimeController;
use Pinepain\SystemInfo\Http\Controllers\VersionController;
use Pinepain\SystemInfo\Http\Middleware\AccessJsonPropertyMiddleware;
use Pinepain\SystemInfo\Http\Middleware\CacheHeadersMiddleware;
use Pinepain\SystemInfo\Http\Middleware\MaybePrettyJsonMiddleware;
use Pinepain\SystemInfo\Http\Middleware\RestrictAccessMiddleware;
use Pinepain\SystemInfo\Http\Middleware\SetVersionHeadersMiddleware;


class SystemInfoServiceProvider extends ServiceProvider
{
    // For pretty complete list of known methods see the https://webconcepts.info/concepts/http-method/
    // here we list only one from MDN (see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods)
    private array $anyVerbs = [
        // most popular and widely supported
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        // misc
        'CONNECT',
        'OPTIONS',
        'TRACE',
        'PURGE',
        // testing
        'TEST',
    ];

    public function boot()
    {
        if (!$this->app->configurationIsCached()) {
            $this->mergeConfigFrom(
                __DIR__ . '/config/system-info.php', 'system-info'
            );
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenTokenCommand::class,

                PingCommand::class,
                StatusCommand::class,
                VersionCommand::class,
            ]);
        }

        if (!$this->app->routesAreCached() && config('system-info.http.root-path')) {
            Route::middleware([
                RestrictAccessMiddleware::class,
                RestrictAccessMiddleware::class,
                CacheHeadersMiddleware::class,
                AccessJsonPropertyMiddleware::class,
                MaybePrettyJsonMiddleware::class,
                SetVersionHeadersMiddleware::class,
            ])
                ->prefix(config('system-info.http.root-path'))
                ->where(['component' => '.*'])
                ->group(function () {
                    Route::get('/version/{component?}', VersionController::class)
                        ->name('system-info.version');

                    Route::get('/status/{component?}', StatusController::class)
                        ->name('system-info.status');

                    Route::get('/ping', PingController::class)
                        ->name('system-info.ping');

                    Route::get('/time/{component?}', TimeController::class)
                        ->name('system-info.time');

                    $orig = Router::$verbs;
                    Router::$verbs = $this->anyVerbs;

                    Route::any('/request/{component?}', RequestController::class)
                        ->name('system-info.request');

                    Route::any('/echo', EchoController::class)
                        ->name('system-info.echo');

                    Router::$verbs = $orig;

                    Route::get('/server/{component?}', ServerController::class)
                        ->name('system-info.server');
                });
        }
    }

    public function register()
    {
        $this->app->singleton(StatusChecker::class, function () {
            return new StatusChecker(
                new DatabasesStatusChecker(),
                new RedisStatusChecker(),
                new CacheStatusChecker(),
            );
        });
    }

    public function provides()
    {
        return [
            StatusChecker::class,
        ];
    }
}
