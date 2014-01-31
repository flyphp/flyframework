<?php namespace Fly\Routing\Matching;

use Fly\Http\Request;
use Fly\Routing\Route;

class UriValidator implements ValidatorInterface {

	/**
	 * Validate a given rule against a route and request.
	 *
	 * @param  \Fly\Routing\Route  $route
	 * @param  \Fly\Http\Request  $request
	 * @return bool
	 */
	public function matches(Route $route, Request $request)
	{
		$path = $request->path() == '/' ? '/' : '/'.$request->path();

		return preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
	}

}