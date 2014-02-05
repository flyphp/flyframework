<?php

use Mockery as m;

class ViewFinderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testBasicViewFinding()
	{
		$finder = $this->getFinder();
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.sword.php')->andReturn(true);

		$this->assertEquals(__DIR__.'/foo.sword.php', $finder->find('foo'));
	}


	public function testCascadingFileLoading()
	{
		$finder = $this->getFinder();
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.sword.php')->andReturn(false);
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.php')->andReturn(true);

		$this->assertEquals(__DIR__.'/foo.php', $finder->find('foo'));
	}


	public function testDirectoryCascadingFileLoading()
	{
		$finder = $this->getFinder();
		$finder->addLocation(__DIR__.'/nested');
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.sword.php')->andReturn(false);
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.php')->andReturn(false);
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/nested/foo.sword.php')->andReturn(true);

		$this->assertEquals(__DIR__.'/nested/foo.sword.php', $finder->find('foo'));
	}


	public function testNamespacedBasicFileLoading()
	{
		$finder = $this->getFinder();
		$finder->addNamespace('foo', __DIR__.'/foo');
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo/bar/baz.sword.php')->andReturn(true);

		$this->assertEquals(__DIR__.'/foo/bar/baz.sword.php', $finder->find('foo::bar.baz'));
	}


	public function testCascadingNamespacedFileLoading()
	{
		$finder = $this->getFinder();
		$finder->addNamespace('foo', __DIR__.'/foo');
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo/bar/baz.sword.php')->andReturn(false);
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo/bar/baz.php')->andReturn(true);

		$this->assertEquals(__DIR__.'/foo/bar/baz.php', $finder->find('foo::bar.baz'));
	}


	public function testDirectoryCascadingNamespacedFileLoading()
	{
		$finder = $this->getFinder();
		$finder->addNamespace('foo', array(__DIR__.'/foo', __DIR__.'/bar'));
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo/bar/baz.sword.php')->andReturn(false);
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo/bar/baz.php')->andReturn(false);
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/bar/bar/baz.sword.php')->andReturn(true);

		$this->assertEquals(__DIR__.'/bar/bar/baz.sword.php', $finder->find('foo::bar.baz'));
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionThrownWhenViewNotFound()
	{
		$finder = $this->getFinder();
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.sword.php')->andReturn(false);
		$finder->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.php')->andReturn(false);

		$finder->find('foo');
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionThrownOnInvalidViewName()
	{
		$finder = $this->getFinder();
		$finder->find('name::');
	}


	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testExceptionThrownWhenNoHintPathIsRegistered()
	{
		$finder = $this->getFinder();
		$finder->find('name::foo');
	}


	public function testAddingExtensionPrependsNotAppends()
	{
		$finder = $this->getFinder();
		$finder->addExtension('baz');
		$extensions = $finder->getExtensions();
		$this->assertEquals('baz', reset($extensions));
	}


	public function testAddingExtensionsReplacesOldOnes()
	{
		$finder = $this->getFinder();
		$finder->addExtension('baz');
		$finder->addExtension('baz');

		$this->assertCount(3, $finder->getExtensions());
	}


	protected function getFinder()
	{
		return new Fly\View\FileViewFinder(m::mock('Fly\Filesystem\Filesystem'), array(__DIR__));
	}

}
