<?php namespace Fly\Support\Facades;

/**
 * @see \Fly\View\Compilers\SwordCompiler
 */
class Sword extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return static::$app['view']->getEngineResolver()->resolve('sword')->getCompiler();
	}

}