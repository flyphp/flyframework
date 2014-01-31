<?php

use Mockery as m;
use Fly\Http\Request;
use Fly\Routing\Route;
use Fly\Routing\Controller;
use Fly\Container\Container;
use Fly\Routing\ControllerDispatcher;

class RoutingControllerDispatcherTest extends PHPUnit_Framework_TestCase {

	public function setUp()
	{
		$_SERVER['ControllerDispatcherTestControllerStub'] = null;
	}


	public function tearDown()
	{
		unset($_SERVER['ControllerDispatcherTestControllerStub']);
		m::close();
	}


	public function testBasicDispatchToMethod()
	{
		$request = Request::create('controller');
		$route = new Route(array('GET'), 'controller', array('uses' => function() {}));
		$route->bind($request);
		$dispatcher = new ControllerDispatcher(m::mock('Fly\Routing\RouteFiltererInterface'), new Container);

		$this->assertNull($_SERVER['ControllerDispatcherTestControllerStub']);

		$response = $dispatcher->dispatch($route, $request, 'ControllerDispatcherTestControllerStub', 'getIndex');
		$this->assertEquals('getIndex', $response);
		$this->assertEquals('setupLayout', $_SERVER['ControllerDispatcherTestControllerStub']);
	}

}


class ControllerDispatcherTestControllerStub extends Controller {

	public function __construct()
	{
		// construct shouldn't affect setupLayout.
	}

	protected function setupLayout()
	{
		$_SERVER['ControllerDispatcherTestControllerStub'] = __FUNCTION__;
	}

	public function getIndex()
	{
		return __FUNCTION__;
	}

	public function getFoo()
	{
		return __FUNCTION__;
	}

}
