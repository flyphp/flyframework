<?php

use Mockery as m;

class FoundationFlyConsoleTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFlyConsoleIsCalledWithProperArguments()
	{
		return;

		$flyconsole = $this->getMock('Fly\Foundation\FlyConsole', array('getFlyConsole'), array($app = new Fly\Foundation\Application));
		$flyconsole->expects($this->once())->method('getFlyConsole')->will($this->returnValue($console = m::mock('StdClass')));
		$console->shouldReceive('find')->once()->with('foo')->andReturn($command = m::mock('StdClass'));
		$command->shouldReceive('run')->once()->with(m::type('Symfony\Component\Console\Input\ArrayInput'), m::type('Symfony\Component\Console\Output\NullOutput'))->andReturnUsing(function($input, $output)
		{
			return $input;
		});

		$input = $flyconsole->call('foo', array('--bar' => 'baz'));
		$this->assertEquals('baz', $input->getParameterOption('--bar'));
	}

}