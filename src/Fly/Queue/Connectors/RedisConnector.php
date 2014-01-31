<?php namespace Fly\Queue\Connectors;

use Fly\Redis\Database;
use Fly\Queue\RedisQueue;

class RedisConnector implements ConnectorInterface {

	/**
	* The Redis database instance.
	*
	 * @var \Fly\Redis\Database
	 */
	protected $redis;

	/**
	 * The connection name.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * Create a new Redis queue connector instance.
	 *
	 * @param  \Fly\Redis\Database  $redis
	 * @param  string|null  $connection
	 * @return void
	 */
	public function __construct(Database $redis, $connection = null)
	{
		$this->redis = $redis;
		$this->connection = $connection;
	}

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Fly\Queue\QueueInterface
	 */
	public function connect(array $config)
	{
		return new RedisQueue($this->redis, $config['queue'], $this->connection);
	}

}