<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Http\Request
 */
class Request extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'request'; }

}