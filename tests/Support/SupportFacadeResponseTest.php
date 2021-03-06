<?php

use Mockery as m;
use Fly\Support\Facades\Response;

class SupportFacadeResponseTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testArrayableSendAsJson()
	{
		$data = m::mock('Fly\Support\Contracts\ArrayableInterface');
		$data->shouldReceive('toArray')->andReturn(array('foo' => 'bar'));

		$response = Response::json($data);
		$this->assertEquals('{"foo":"bar"}', $response->getContent());
	}

}