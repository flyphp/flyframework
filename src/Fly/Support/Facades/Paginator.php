<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Pagination\Environment
 */
class Paginator extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'paginator'; }

}