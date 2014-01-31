<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\Queue\QueueManager
 * @see \Fly\Queue\Queue
 */
class Queue extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'queue'; }

}