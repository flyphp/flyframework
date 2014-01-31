<?php namespace Fly\Foundation;

use Fly\Console\Application as ConsoleApplication;

class FlyConsole {

	/**
	 * The application instance.
	 *
	 * @var \Fly\Foundation\Application
	 */
	protected $app;

	/**
	 * The FlyConsole console instance.
	 *
	 * @var  \Fly\Console\Application
	 */
	protected $flyconsole;

	/**
	 * Create a new FlyConsole command runner instance.
	 *
	 * @param  \Fly\Foundation\Application  $app
	 * @return void
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * Get the FlyConsole console instance.
	 *
	 * @return \Fly\Console\Application
	 */
	protected function getFlyConsole()
	{
		if ( ! is_null($this->flyconsole)) return $this->flyconsole;

		$this->app->loadDeferredProviders();

		$this->flyconsole = ConsoleApplication::make($this->app);

		return $this->flyconsole->boot();
	}

	/**
	 * Dynamically pass all missing methods to console FlyConsole.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->getFlyConsole(), $method), $parameters);
	}

}
