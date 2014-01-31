<?php namespace Fly\Queue\Console;

use Fly\Console\Command;
use Fly\Filesystem\Filesystem;

class FailedTableCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'queue:failed-table';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a migration for the failed queue jobs database table';

	/**
	 * The filesystem instance.
	 *
	 * @var \Fly\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new session table command instance.
	 *
	 * @param  \Fly\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		parent::__construct();

		$this->files = $files;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$fullPath = $this->createBaseMigration();

		$this->files->put($fullPath, $this->files->get(__DIR__.'/stubs/failed_jobs.stub'));

		$this->info('Migration created successfully!');
	}

	/**
	 * Create a base migration file for the table.
	 *
	 * @return string
	 */
	protected function createBaseMigration()
	{
		$name = 'create_failed_jobs_table';

		$path = $this->flyphp['path'].'/database/migrations';

		return $this->flyphp['migration.creator']->create($name, $path);
	}

}