<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Cache\CacheManager
 * @see \Fly\Cache\Repository
 */
class Cache extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'cache'; }

}