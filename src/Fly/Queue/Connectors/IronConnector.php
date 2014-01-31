<?php namespace Fly\Queue\Connectors;

use IronMQ;
use Fly\Http\Request;
use Fly\Queue\IronQueue;
use Fly\Encryption\Encrypter;

class IronConnector implements ConnectorInterface {

	/**
	 * The encrypter instance.
	 *
	 * @var \Fly\Encryption\Encrypter
	 */
	protected $crypt;

	/**
	 * The current request instance.
	 *
	 * @var \Fly\Http\Request;
	 */
	protected $request;

	/**
	 * Create a new Iron connector instance.
	 *
	 * @param  \Fly\Encryption\Encrypter  $crypt
	 * @param  \Fly\Http\Request  $request
	 * @return void
	 */
	public function __construct(Encrypter $crypt, Request $request)
	{
		$this->crypt = $crypt;
		$this->request = $request;
	}

	/**
	 * Establish a queue connection.
	 *
	 * @param  array  $config
	 * @return \Fly\Queue\QueueInterface
	 */
	public function connect(array $config)
	{
		$ironConfig = array('token' => $config['token'], 'project_id' => $config['project']);

		if (isset($config['host'])) $ironConfig['host'] = $config['host'];

		return new IronQueue(new IronMQ($ironConfig), $this->crypt, $this->request, $config['queue']);
	}

}