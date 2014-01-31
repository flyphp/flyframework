<?php namespace Fly\Cache\Console;

use Fly\Console\Command;
use Fly\Cache\CacheManager;
use Fly\Filesystem\Filesystem;

class ClearCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cache:clear';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Flush the application cache";

	/**
	 * The cache manager instance.
	 *
	 * @var \Fly\Cache\CacheManager
	 */
	protected $cache;

	/**
	 * The file system instance.
	 *
	 * @var \Fly\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new cache clear command instance.
	 *
	 * @param  \Fly\Cache\CacheManager  $cache
	 * @param  \Fly\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(CacheManager $cache, Filesystem $files)
	{
		parent::__construct();

		$this->cache = $cache;
		$this->files = $files;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->cache->flush();

		$this->files->delete($this->flyphp['config']['app.manifest'].'/services.json');

		$this->info('Application cache cleared!');
	}

}