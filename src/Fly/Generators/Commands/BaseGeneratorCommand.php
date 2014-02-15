<?php namespace Fly\Generators\Commands;

use Fly\Generators\Generators\ViewGenerator;
use Fly\Console\Command;
use Fly\Filesystem\Filesystem as File;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class BaseGeneratorCommand extends Command {

    /**
     * Built-in Templates Base Path
     * @var [string]
     */
    protected $templateCorePath = __DIR__.'/../Generators/templates/';

    /**
     * App Templates Base Path
     * @var [string]
     */
    protected $templateAppPath = app_path().'/generators/templates/';


    /**
     * File system instance
     * @var File
     */
    protected $file;


    /**
     * Constructor
     *
     * @param $file
     */
    public function __construct(File $file)
    {
        parent::__construct();

        $this->file = $file;
    }


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $path = $this->getPath();
        $template = $this->option('template');

        $this->printResult($this->generator->make($path, $template), $path);
    }

    /**
     * Provide user feedback, based on success or not.
     *
     * @param  boolean $successful
     * @param  string $path
     * @return void
     */
    protected function printResult($successful, $path)
    {
        if ($successful)
        {
            return $this->info("Created {$path}");
        }

        $this->error("Could not create {$path}");
    }

    /**
     * Get the path to the file that should be generated.
     *
     * @return string
     */
    protected function getPath()
    {
       return $this->option('path') . '/' . strtolower($this->argument('name')) . '.sword.php';
    }


    /**
     * 
     */
    protected function getTemplatePath($template)
    {
        if ($this->file->exists($this->templateAppPath.$template))
        {
            return $this->templateAppPath.$template;
        }

        return $this->templateCorePath.$template;
    }

}