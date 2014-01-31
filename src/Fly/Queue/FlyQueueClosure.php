<?php

class FlyQueueClosure {

	/**
	 * Fire the Closure based queue job.
	 *
	 * @param  \Fly\Queue\Jobs\Job  $job
	 * @param  array  $data
	 * @return void
	 */
	public function fire($job, $data)
	{
		$closure = unserialize($data['closure']);

		$closure($job);
	}

}