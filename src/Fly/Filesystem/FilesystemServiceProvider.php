<?php namespace Fly\Filesystem;

use Fly\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('files', function() { return new Filesystem; });
	}

}