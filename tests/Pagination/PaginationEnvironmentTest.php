<?php

use Mockery as m;
use Fly\Pagination\Environment;

class PaginationEnvironmentTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCreationOfEnvironment()
	{
		$env = $this->getEnvironment();
	}


	public function testPaginatorCanBeCreated()
	{
		$env = $this->getEnvironment();
		$request = Fly\Http\Request::create('http://foo.com', 'GET');
		$env->setRequest($request);

		$this->assertInstanceOf('Fly\Pagination\Paginator', $env->make(array('foo', 'bar'), 2, 2));
	}


	public function testPaginationViewCanBeCreated()
	{
		$env = $this->getEnvironment();
		$paginator = m::mock('Fly\Pagination\Paginator');
		$env->getViewDriver()->shouldReceive('make')->once()->with('pagination::slider', array('environment' => $env, 'paginator' => $paginator))->andReturn('foo');

		$this->assertEquals('foo', $env->getPaginationView($paginator));
	}


	public function testCurrentPageCanBeRetrieved()
	{
		$env = $this->getEnvironment();
		$request = Fly\Http\Request::create('http://foo.com?page=2', 'GET');
		$env->setRequest($request);

		$this->assertEquals(2, $env->getCurrentPage());

		$env = $this->getEnvironment();
		$request = Fly\Http\Request::create('http://foo.com?page=-1', 'GET');
		$env->setRequest($request);

		$this->assertEquals(1, $env->getCurrentPage());
	}


	public function testSettingCurrentUrlOverrulesRequest()
	{
		$env = $this->getEnvironment();
		$request = Fly\Http\Request::create('http://foo.com?page=2', 'GET');
		$env->setRequest($request);
		$env->setCurrentPage(3);

		$this->assertEquals(3, $env->getCurrentPage());
	}


	public function testCurrentUrlCanBeRetrieved()
	{
		$env = $this->getEnvironment();
		$request = Fly\Http\Request::create('http://foo.com/bar?page=2', 'GET');
		$env->setRequest($request);

		$this->assertEquals('http://foo.com/bar', $env->getCurrentUrl());

		$env = $this->getEnvironment();
		$request = Fly\Http\Request::create('http://foo.com?page=2', 'GET');
		$env->setRequest($request);

		$this->assertEquals('http://foo.com', $env->getCurrentUrl());
	}


	public function testOverridingPageParam()
	{
		$env = $this->getEnvironment();
		$this->assertEquals('page', $env->getPageName());
		$env->setPageName('foo');
		$this->assertEquals('foo', $env->getPageName());
	}


	protected function getEnvironment()
	{
		$request = m::mock('Fly\Http\Request');
		$view = m::mock('Fly\View\Environment');
		$trans = m::mock('Symfony\Component\Translation\TranslatorInterface');
		$view->shouldReceive('addNamespace')->once()->with('pagination', realpath(__DIR__.'/../../src/Fly/Pagination').'/views');

		return new Environment($request, $view, $trans, 'page');
	}

}
