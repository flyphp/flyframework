<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Session\SessionManager
 * @see \Fly\Session\Store
 */
class Session extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'session'; }

}