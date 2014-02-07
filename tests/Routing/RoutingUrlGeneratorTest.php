<?php

use Fly\Routing\UrlGenerator;

class RoutingUrlGeneratorTest extends PHPUnit_Framework_TestCase {

	public function testBasicGeneration()
	{
		$url = new UrlGenerator(
			$routes = new Fly\Routing\RouteCollection,
			$request = Fly\Http\Request::create('http://www.foo.com/')
		);

		$this->assertEquals('http://www.foo.com/foo/bar', $url->to('foo/bar'));
		$this->assertEquals('https://www.foo.com/foo/bar', $url->to('foo/bar', array(), true));
		$this->assertEquals('https://www.foo.com/foo/bar/baz/boom', $url->to('foo/bar', array('baz', 'boom'), true));

		/**
		 * Test HTTPS request URL geneation...
		 */
		$url = new UrlGenerator(
			$routes = new Fly\Routing\RouteCollection,
			$request = Fly\Http\Request::create('https://www.foo.com/')
		);

		$this->assertEquals('https://www.foo.com/foo/bar', $url->to('foo/bar'));

		/**
		 * Test asset URL generation...
		 */
		$url = new UrlGenerator(
			$routes = new Fly\Routing\RouteCollection,
			$request = Fly\Http\Request::create('http://www.foo.com/index.php/')
		);

		$this->assertEquals('http://www.foo.com/foo/bar', $url->asset('foo/bar'));
		$this->assertEquals('https://www.foo.com/foo/bar', $url->asset('foo/bar', true));
	}


	public function testBasicRouteGeneration()
	{
		$url = new UrlGenerator(
			$routes = new Fly\Routing\RouteCollection,
			$request = Fly\Http\Request::create('http://www.foo.com/')
		);

		/**
		 * Empty Named Route
		 */
		$route = new Fly\Routing\Route(array('GET'), '/', array('as' => 'plain'));
		$routes->add($route);

		/**
		 * Named Routes
		 */
		$route = new Fly\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo'));
		$routes->add($route);

		/**
		 * Parameters...
		 */
		$route = new Fly\Routing\Route(array('GET'), 'foo/bar/{baz}/breeze/{boom}', array('as' => 'bar'));
		$routes->add($route);

		/**
		 * HTTPS...
		 */
		$route = new Fly\Routing\Route(array('GET'), 'foo/bar', array('as' => 'baz', 'https'));
		$routes->add($route);

		/**
		 * Controller Route Route
		 */
		$route = new Fly\Routing\Route(array('GET'), 'foo/bar', array('controller' => 'foo@bar'));
		$routes->add($route);

		/**
		 * Non ASCII routes
		 */
		$route = new Fly\Routing\Route(array('GET'), 'foo/bar/åαф/{baz}', array('as' => 'foobarbaz'));
		$routes->add($route);

		$this->assertEquals('/', $url->route('plain', array(), false));
		$this->assertEquals('/?foo=bar', $url->route('plain', array('foo' => 'bar'), false));
		$this->assertEquals('http://www.foo.com/foo/bar', $url->route('foo'));
		$this->assertEquals('/foo/bar', $url->route('foo', array(), false));
		$this->assertEquals('/foo/bar?foo=bar', $url->route('foo', array('foo' => 'bar'), false));
		$this->assertEquals('http://www.foo.com/foo/bar/allan/breeze/otwell?fly=wall', $url->route('bar', array('allan', 'otwell', 'fly' => 'wall')));
		$this->assertEquals('http://www.foo.com/foo/bar/otwell/breeze/allan?fly=wall', $url->route('bar', array('boom' => 'allan', 'baz' => 'otwell', 'fly' => 'wall')));
		$this->assertEquals('/foo/bar/allan/breeze/otwell?fly=wall', $url->route('bar', array('allan', 'otwell', 'fly' => 'wall'), false));
		$this->assertEquals('https://www.foo.com/foo/bar', $url->route('baz'));
		$this->assertEquals('http://www.foo.com/foo/bar', $url->action('foo@bar'));
		$this->assertEquals('http://www.foo.com/foo/bar/allan/breeze/otwell?wall&woz', $url->route('bar', array('wall', 'woz', 'boom' => 'otwell', 'baz' => 'allan')));
		$this->assertEquals('http://www.foo.com/foo/bar/allan/breeze/otwell?wall&woz', $url->route('bar', array('allan', 'otwell', 'wall', 'woz')));
		$this->assertEquals('http://www.foo.com/foo/bar/%C3%A5%CE%B1%D1%84/%C3%A5%CE%B1%D1%84', $url->route('foobarbaz', array('baz' => 'åαф')));

	}


	public function testRoutesMaintainRequestScheme()
	{
		$url = new UrlGenerator(
			$routes = new Fly\Routing\RouteCollection,
			$request = Fly\Http\Request::create('https://www.foo.com/')
		);

		/**
		 * Named Routes
		 */
		$route = new Fly\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo'));
		$routes->add($route);

		$this->assertEquals('https://www.foo.com/foo/bar', $url->route('foo'));
	}


	public function testHttpOnlyRoutes()
	{
		$url = new UrlGenerator(
			$routes = new Fly\Routing\RouteCollection,
			$request = Fly\Http\Request::create('https://www.foo.com/')
		);

		/**
		 * Named Routes
		 */
		$route = new Fly\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo', 'http'));
		$routes->add($route);

		$this->assertEquals('http://www.foo.com/foo/bar', $url->route('foo'));
	}


	public function testRoutesWithDomains()
	{
		$url = new UrlGenerator(
			$routes = new Fly\Routing\RouteCollection,
			$request = Fly\Http\Request::create('http://www.foo.com/')
		);

		$route = new Fly\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo', 'domain' => 'sub.foo.com'));
		$routes->add($route);

		/**
		 * Wildcards & Domains...
		 */
		$route = new Fly\Routing\Route(array('GET'), 'foo/bar/{baz}', array('as' => 'bar', 'domain' => 'sub.{foo}.com'));
		$routes->add($route);

		$this->assertEquals('http://sub.foo.com/foo/bar', $url->route('foo'));
		$this->assertEquals('http://sub.allan.com/foo/bar/otwell', $url->route('bar', array('allan', 'otwell')));
		$this->assertEquals('/foo/bar/otwell', $url->route('bar', array('allan', 'otwell'), false));
	}


	public function testRoutesWithDomainsAndPorts()
	{
		$url = new UrlGenerator(
			$routes = new Fly\Routing\RouteCollection,
			$request = Fly\Http\Request::create('http://www.foo.com:8080/')
		);

		$route = new Fly\Routing\Route(array('GET'), 'foo/bar', array('as' => 'foo', 'domain' => 'sub.foo.com'));
		$routes->add($route);

		/**
		 * Wildcards & Domains...
		 */
		$route = new Fly\Routing\Route(array('GET'), 'foo/bar/{baz}', array('as' => 'bar', 'domain' => 'sub.{foo}.com'));
		$routes->add($route);

		$this->assertEquals('http://sub.foo.com:8080/foo/bar', $url->route('foo'));
		$this->assertEquals('http://sub.allan.com:8080/foo/bar/otwell', $url->route('bar', array('allan', 'otwell')));
	}


	public function testUrlGenerationForControllers()
	{
		$url = new UrlGenerator(
			$routes = new Fly\Routing\RouteCollection,
			$request = Fly\Http\Request::create('http://www.foo.com:8080/')
		);

		$route = new Fly\Routing\Route(array('GET'), 'foo/{one}/{two?}/{three?}', array('as' => 'foo', function() {}));
		$routes->add($route);

		$this->assertEquals('http://www.foo.com:8080/foo', $url->route('foo'));
	}

}