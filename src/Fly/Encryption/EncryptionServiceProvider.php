<?php namespace Fly\Encryption;

use Fly\Support\ServiceProvider;

class EncryptionServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('encrypter', function($app)
		{
			return new Encrypter($app['config']['app.key']);
		});
	}

}