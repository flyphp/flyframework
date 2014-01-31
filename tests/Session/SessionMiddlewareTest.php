<?php

use Mockery as m;

class SessionMiddlewareTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSessionIsProperlyStartedAndClosed()
	{
		$request = Symfony\Component\HttpFoundation\Request::create('/', 'GET');
		$response = new Symfony\Component\HttpFoundation\Response;

		$middle = new Fly\Session\Middleware(
			$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'),
			$manager = m::mock('Fly\Session\SessionManager')
		);

		$manager->shouldReceive('getSessionConfig')->andReturn(array(
			'driver' => 'file',
			'lottery' => array(100, 100),
			'path' => '/',
			'domain' => null,
			'lifetime' => 120,
			'expire_on_close' => false,
		));

		$manager->shouldReceive('driver')->andReturn($driver = m::mock('Fly\Session\Store')->makePartial());
		$driver->shouldReceive('setRequestOnHandler')->once()->with($request);
		$driver->shouldReceive('start')->once();
		$app->shouldReceive('handle')->once()->with($request, Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);
		$driver->shouldReceive('save')->once();
		$driver->shouldReceive('getHandler')->andReturn($handler = m::mock('StdClass'));
		$handler->shouldReceive('gc')->once()->with(120 * 60);
		$driver->shouldReceive('getName')->andReturn('name');
		$driver->shouldReceive('getId')->andReturn(1);

		$middleResponse = $middle->handle($request);

		$this->assertTrue($response === $middleResponse);
		$this->assertEquals(1, head($response->headers->getCookies())->getValue());
	}


	public function testSessionIsNotUsedWhenNoDriver()
	{
		$request = Symfony\Component\HttpFoundation\Request::create('/', 'GET');
		$response = new Symfony\Component\HttpFoundation\Response;
		$middle = new Fly\Session\Middleware(
			$app = m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'),
			$manager = m::mock('Fly\Session\SessionManager')
		);
		$manager->shouldReceive('getSessionConfig')->andReturn(array(
			'driver' => null,
		));
		$app->shouldReceive('handle')->once()->with($request, Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST, true)->andReturn($response);
		$middleResponse = $middle->handle($request);

		$this->assertTrue($response === $middleResponse);
	}


	public function testCheckingForRequestUsingArraySessions()
	{
		$middleware = new Fly\Session\Middleware(
			m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'),
			$manager = m::mock('Fly\Session\SessionManager'),
			function() { return true; }
		);

		$manager->shouldReceive('setDefaultDriver')->once()->with('array');

		$middleware->checkRequestForArraySessions(new Symfony\Component\HttpFoundation\Request);
	}

}