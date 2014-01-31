<?php namespace Fly\Foundation\Providers;

use Fly\Foundation\FlyConsole;
use Fly\Support\ServiceProvider;
use Fly\Foundation\Console\TailCommand;
use Fly\Foundation\Console\ChangesCommand;
use Fly\Foundation\Console\EnvironmentCommand;

class FlyConsoleServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('flyconsole', function($app)
		{
			return new FlyConsole($app);
		});

		$this->app->bindShared('command.tail', function($app)
		{
			return new TailCommand;
		});

		$this->app->bindShared('command.changes', function($app)
		{
			return new ChangesCommand;
		});

		$this->app->bindShared('command.environment', function($app)
		{
			return new EnvironmentCommand;
		});

		$this->commands('command.tail', 'command.changes', 'command.environment');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('flyconsole', 'command.changes', 'command.environment');
	}

}