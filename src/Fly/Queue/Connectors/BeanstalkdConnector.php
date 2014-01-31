<?php namespace Fly\Queue\Connectors;

use Fly\Queue\BeanstalkdQueue;
use Pheanstalk_Pheanstalk as Pheanstalk;

class BeanstalkdConnector implements ConnectorInterface {

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Fly\Queue\QueueInterface
	 */
	public function connect(array $config)
	{
		$pheanstalk = new Pheanstalk($config['host']);

		return new BeanstalkdQueue($pheanstalk, $config['queue']);
	}

}