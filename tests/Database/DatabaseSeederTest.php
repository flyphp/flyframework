<?php

use Mockery as m;
use Fly\Database\Seeder;

class DatabaseSeederTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCallResolveTheClassAndCallsRun()
	{
		$seeder = new Seeder;
		$seeder->setContainer($container = m::mock('Fly\Container\Container'));
		$output = m::mock('Symfony\Component\Console\Output\OutputInterface');
		$output->shouldReceive('writeln')->once()->andReturn('foo');
		$command = m::mock('Fly\Console\Command');
		$command->shouldReceive('getOutput')->once()->andReturn($output);
		$seeder->setCommand($command);
		$container->shouldReceive('make')->once()->with('ClassName')->andReturn($child = m::mock('StdClass'));
		$child->shouldReceive('setContainer')->once()->with($container)->andReturn($child);
		$child->shouldReceive('setCommand')->once()->with($command)->andReturn($child);
		$child->shouldReceive('run')->once();

		$seeder->call('ClassName');
	}

	public function testSetContainer()
	{
		$seeder = new Seeder;
		$container = m::mock('Fly\Container\Container');
		$this->assertEquals($seeder->setContainer($container), $seeder);
	}

	public function testSetCommand()
	{
		$seeder = new Seeder;
		$command = m::mock('Fly\Console\Command');
		$this->assertEquals($seeder->setCommand($command), $seeder);
	}

}