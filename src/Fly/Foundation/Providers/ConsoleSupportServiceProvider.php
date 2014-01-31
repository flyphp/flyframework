<?php namespace Fly\Foundation\Providers;

use Fly\Support\ServiceProvider;

class ConsoleSupportServiceProvider extends ServiceProvider {

	/**
	 * The provider class names.
	 *
	 * @var array
	 */
	protected $providers = array(
		'Fly\Foundation\Providers\CommandCreatorServiceProvider',
		'Fly\Foundation\Providers\ComposerServiceProvider',
		'Fly\Foundation\Providers\KeyGeneratorServiceProvider',
		'Fly\Foundation\Providers\MaintenanceServiceProvider',
		'Fly\Foundation\Providers\OptimizeServiceProvider',
		'Fly\Foundation\Providers\PublisherServiceProvider',
		'Fly\Foundation\Providers\RouteListServiceProvider',
		'Fly\Foundation\Providers\ServerServiceProvider',
		'Fly\Foundation\Providers\TinkerServiceProvider',
		'Fly\Queue\FailConsoleServiceProvider',
	);

	/**
	 * An array of the service provider instances.
	 *
	 * @var array
	 */
	protected $instances = array();

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
		$this->instances = array();

		foreach ($this->providers as $provider)
		{
			$this->instances[] = $this->app->register($provider);
		}
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		$provides = array();

		foreach ($this->providers as $provider)
		{
			$instance = $this->app->resolveProviderClass($provider);

			$provides = array_merge($provides, $instance->provides());
		}

		return $provides;
	}

}