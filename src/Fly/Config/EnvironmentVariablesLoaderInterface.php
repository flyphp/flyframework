<?php namespace Fly\Config;

interface EnvironmentVariablesLoaderInterface {

	/**
	 * Load the environment variables for the given environment.
	 *
	 * @param  string  $environment
	 * @return array
	 */
	public function load($environment = null);

}