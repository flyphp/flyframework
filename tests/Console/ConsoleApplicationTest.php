<?php

use Mockery as m;

class ConsoleApplicationTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testAddSetsLaravelInstance()
	{
		$app = $this->getMock('Fly\Console\Application', array('addToParent'));
		$app->setFlyphp('foo');
		$command = m::mock('Fly\Console\Command');
		$command->shouldReceive('setFlyphp')->once()->with('foo');
		$app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
		$result = $app->add($command);

		$this->assertEquals($command, $result);
	}


	public function testLaravelNotSetOnSymfonyCommands()
	{
		$app = $this->getMock('Fly\Console\Application', array('addToParent'));
		$app->setFlyphp('foo');
		$command = m::mock('Symfony\Component\Console\Command\Command');
		$command->shouldReceive('setFlyphp')->never();
		$app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
		$result = $app->add($command);

		$this->assertEquals($command, $result);
	}


	public function testResolveAddsCommandViaApplicationResolution()
	{
		$app = $this->getMock('Fly\Console\Application', array('addToParent'));
		$command = m::mock('Symfony\Component\Console\Command\Command');
		$app->setFlyphp(array('foo' => $command));
		$app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
		$result = $app->resolve('foo');

		$this->assertEquals($command, $result);
	}


	public function testResolveCommandsCallsResolveForAllCommandsItsGiven()
	{
		$app = m::mock('Fly\Console\Application[resolve]');
		$app->shouldReceive('resolve')->twice()->with('foo');
		$app->resolveCommands('foo', 'foo');
	}


	public function testResolveCommandsCallsResolveForAllCommandsItsGivenViaArray()
	{
		$app = m::mock('Fly\Console\Application[resolve]');
		$app->shouldReceive('resolve')->twice()->with('foo');
		$app->resolveCommands(array('foo', 'foo'));
	}

}