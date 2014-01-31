<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Foundation\FlyConsole
 */
class FlyConsole extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'flyconsole'; }

}