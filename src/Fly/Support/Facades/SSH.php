<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Remote\RemoteManager
 * @see \Fly\Remote\Connection
 */
class SSH extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'remote'; }

}