<?php namespace Fly\Support\Contracts;

interface MessageProviderInterface {

	/**
	 * Get the messages for the instance.
	 *
	 * @return \Fly\Support\MessageBag
	 */
	public function getMessageBag();

}