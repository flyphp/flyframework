<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Config\Repository
 */
class Config extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'config'; }

}