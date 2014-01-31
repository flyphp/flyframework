<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Auth\AuthManager
 * @see \Fly\Auth\Guard
 */
class Auth extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'auth'; }

}