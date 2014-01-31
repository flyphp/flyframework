<?php

use Mockery as m;
use Fly\Queue\Worker;

class QueueWorkerTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testJobIsPoppedOffQueueAndProcessed()
	{
		$worker = $this->getMock('Fly\Queue\Worker', array('process'), array($manager = m::mock('Fly\Queue\QueueManager')));
		$manager->shouldReceive('connection')->once()->with('connection')->andReturn($connection = m::mock('StdClass'));
		$manager->shouldReceive('getName')->andReturn('connection');
		$job = m::mock('Fly\Queue\Jobs\Job');
		$connection->shouldReceive('pop')->once()->with('queue')->andReturn($job);
		$worker->expects($this->once())->method('process')->with($this->equalTo('connection'), $this->equalTo($job), $this->equalTo(0), $this->equalTo(0));

		$worker->pop('connection', 'queue');
	}


	public function testJobIsPoppedOffFirstQueueInListAndProcessed()
	{
		$worker = $this->getMock('Fly\Queue\Worker', array('process'), array($manager = m::mock('Fly\Queue\QueueManager')));
		$manager->shouldReceive('connection')->once()->with('connection')->andReturn($connection = m::mock('StdClass'));
		$manager->shouldReceive('getName')->andReturn('connection');
		$job = m::mock('Fly\Queue\Jobs\Job');
		$connection->shouldReceive('pop')->once()->with('queue1')->andReturn(null);
		$connection->shouldReceive('pop')->once()->with('queue2')->andReturn($job);
		$worker->expects($this->once())->method('process')->with($this->equalTo('connection'), $this->equalTo($job), $this->equalTo(0), $this->equalTo(0));

		$worker->pop('connection', 'queue1,queue2');
	}


	public function testWorkerSleepsIfNoJobIsPresentAndSleepIsEnabled()
	{
		$worker = $this->getMock('Fly\Queue\Worker', array('process', 'sleep'), array($manager = m::mock('Fly\Queue\QueueManager')));
		$manager->shouldReceive('connection')->once()->with('connection')->andReturn($connection = m::mock('StdClass'));
		$connection->shouldReceive('pop')->once()->with('queue')->andReturn(null);
		$worker->expects($this->never())->method('process');
		$worker->expects($this->once())->method('sleep')->with($this->equalTo(1));

		$worker->pop('connection', 'queue', 0, 128, true);
	}


	public function testWorkerLogsJobToFailedQueueIfMaxTriesHasBeenExceeded()
	{
		$worker = new Fly\Queue\Worker(m::mock('Fly\Queue\QueueManager'), $failer = m::mock('Fly\Queue\Failed\FailedJobProviderInterface'));
		$job = m::mock('Fly\Queue\Jobs\Job');
		$job->shouldReceive('attempts')->once()->andReturn(10);
		$job->shouldReceive('getQueue')->once()->andReturn('queue');
		$job->shouldReceive('getRawBody')->once()->andReturn('body');
		$job->shouldReceive('delete')->once();
		$failer->shouldReceive('log')->once()->with('connection', 'queue', 'body');

		$worker->process('connection', $job, 3, 0);
	}


	public function testProcessFiresJobAndAutoDeletesIfTrue()
	{
		$worker = new Fly\Queue\Worker(m::mock('Fly\Queue\QueueManager'));
		$job = m::mock('Fly\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once();
		$job->shouldReceive('autoDelete')->once()->andReturn(true);
		$job->shouldReceive('delete')->once();

		$worker->process('connection', $job, 0, 0);
	}


	public function testProcessFiresJobAndDoesntCallDeleteIfJobDoesntAutoDelete()
	{
		$worker = new Fly\Queue\Worker(m::mock('Fly\Queue\QueueManager'));
		$job = m::mock('Fly\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once();
		$job->shouldReceive('autoDelete')->once()->andReturn(false);
		$job->shouldReceive('delete')->never();

		$worker->process('connection', $job, 0, 0);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testJobIsReleasedWhenExceptionIsThrown()
	{
		$worker = new Fly\Queue\Worker(m::mock('Fly\Queue\QueueManager'));
		$job = m::mock('Fly\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once()->andReturnUsing(function() { throw new RuntimeException; });
		$job->shouldReceive('isDeleted')->once()->andReturn(false);
		$job->shouldReceive('release')->once()->with(5);

		$worker->process('connection', $job, 0, 5);
	}


	/**
	 * @expectedException RuntimeException
	 */
	public function testJobIsNotReleasedWhenExceptionIsThrownButJobIsDeleted()
	{
		$worker = new Fly\Queue\Worker(m::mock('Fly\Queue\QueueManager'));
		$job = m::mock('Fly\Queue\Jobs\Job');
		$job->shouldReceive('fire')->once()->andReturnUsing(function() { throw new RuntimeException; });
		$job->shouldReceive('isDeleted')->once()->andReturn(true);
		$job->shouldReceive('release')->never();

		$worker->process('connection', $job, 0, 5);
	}

}