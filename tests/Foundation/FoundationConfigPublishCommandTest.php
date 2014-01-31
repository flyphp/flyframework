<?php

use Mockery as m;

class FoundationConfigPublishCommandTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testCommandCallsPublisherWithProperPackageName()
	{
		$command = new Fly\Foundation\Console\ConfigPublishCommand($pub = m::mock('Fly\Foundation\ConfigPublisher'));
		$pub->shouldReceive('publishPackage')->once()->with('foo');
		$command->run(new Symfony\Component\Console\Input\ArrayInput(array('package' => 'foo')), new Symfony\Component\Console\Output\NullOutput);
	}

}