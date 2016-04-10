<?php

namespace TillKruss\LaravelTactician;

use League\Tactician\CommandBus;
use Illuminate\Support\ServiceProvider;
use TillKruss\LaravelTactician\Executer;
use League\Tactician\Handler\Locator\HandlerLocator;
use League\Tactician\Handler\CommandHandlerMiddleware;
use League\Tactician\Handler\MethodNameInflector\MethodNameInflector;
use League\Tactician\Handler\CommandNameExtractor\CommandNameExtractor;

class TacticianServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $config = __DIR__.'/config/tactician.php';

        $this->publishes([$config => config_path('tactician.php')]);
        $this->mergeConfigFrom($config, 'tactician');
    }

    /**
     * Register the tactician services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommandBus();
        $this->registerCommandExecuter();
        $this->registerCommandHandlerMiddleware();
        $this->bindTacticianInterfaces();
    }

    /**
     * Register the command bus instance.
     *
     * @return void
     */
    protected function registerCommandBus()
    {
        $this->app->singleton('League\Tactician\CommandBus', function ($app) {
            return $app->make('tactician.commandbus');
        });

        $this->app->singleton('tactician.commandbus', function ($app) {
            $middleware = array_map(function ($name) use ($app) {
                return is_string($name) ? $app->make($name) : $name;
            }, $app->config->get('tactician.middleware'));

            return new CommandBus($middleware);
        });
    }

    /**
     * Register the command bus executer instance.
     *
     * @return void
     */
    protected function registerCommandExecuter()
    {
        $this->app->singleton('TillKruss\LaravelTactician\Contracts\Executer', function ($app) {
            return $app->make(Executer::class);
        });
    }

    /**
     * Register the command handler middleware instance.
     *
     * @return void
     */
    protected function registerCommandHandlerMiddleware()
    {
        $this->app->bind('League\Tactician\Handler\CommandHandlerMiddleware', function ($app) {
            return $app->make('tactician.middleware.commandhandler');
        });

        $this->app->bind('tactician.middleware.commandhandler', function ($app) {
            return new CommandHandlerMiddleware(
                $app->make(CommandNameExtractor::class),
                $app->make(HandlerLocator::class),
                $app->make(MethodNameInflector::class)
            );
        });
    }

    /**
     * Register the tactician interface instances.
     *
     * @return void
     */
    protected function bindTacticianInterfaces()
    {
        $this->app->bind('League\Tactician\Handler\Locator\HandlerLocator', function ($app) {
            return $app->make($app->config->get('tactician.locator'));
        });

        $this->app->bind('League\Tactician\Handler\MethodNameInflector\MethodNameInflector', function ($app) {
            return $app->make($app->config->get('tactician.inflector'));
        });

        $this->app->bind('League\Tactician\Handler\CommandNameExtractor\CommandNameExtractor', function ($app) {
            return $app->make($app->config->get('tactician.extractor'));
        });
    }
}
