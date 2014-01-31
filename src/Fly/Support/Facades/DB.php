<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Database\DatabaseManager
 * @see \Fly\Database\Connection
 */
class DB extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'db'; }

}