<?php namespace Fly\Routing\Matching;

use Fly\Http\Request;
use Fly\Routing\Route;

class MethodValidator implements ValidatorInterface {

	/**
	 * Validate a given rule against a route and request.
	 *
	 * @param  \Fly\Routing\Route  $route
	 * @param  \Fly\Http\Request  $request
	 * @return bool
	 */
	public function matches(Route $route, Request $request)
	{
		return in_array($request->getMethod(), $route->methods());
	}

}