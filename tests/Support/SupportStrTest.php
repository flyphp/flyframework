<?php

use Fly\Support\Str;

class SupportStrTest extends PHPUnit_Framework_TestCase {

	/**
	* Test the Str::words method.
	*
	* @group laravel
	*/
	public function testStringCanBeLimitedByWords()
	{
		$this->assertEquals('Allan...', Str::words('Allan Freitas', 1));
		$this->assertEquals('Allan___', Str::words('Allan Freitas', 1, '___'));
		$this->assertEquals('Allan Freitas', Str::words('Allan Freitas', 3));
	}


	public function testStringTrimmedOnlyWhereNecessary()
	{
		$this->assertEquals(' Allan Freitas ', Str::words(' Allan Freitas ', 3));
		$this->assertEquals(' Allan...', Str::words(' Allan Freitas ', 1));
	}

	public function testStringTitle()
	{
		$this->assertEquals('Allan Freitas', Str::title('allan freitas'));
		$this->assertEquals('Allan Freitas', Str::title('alLaN fREitas'));
	}

	public function testStringWithoutWordsDoesntProduceError()
	{
		$nbsp = chr(0xC2).chr(0xA0);
		$this->assertEquals(' ', Str::words(' '));
		$this->assertEquals($nbsp, Str::words($nbsp));
	}


	public function testStringMacros()
	{
		Fly\Support\Str::macro(__CLASS__, function() { return 'foo'; });
		$this->assertEquals('foo', Str::SupportStrTest());
	}


	public function testStartsWith()
	{
		$this->assertTrue(Str::startsWith('jason', 'jas'));
		$this->assertTrue(Str::startsWith('jason', 'jason'));
		$this->assertTrue(Str::startsWith('jason', array('jas')));
		$this->assertFalse(Str::startsWith('jason', 'day'));
		$this->assertFalse(Str::startsWith('jason', array('day')));
		$this->assertFalse(Str::startsWith('jason', ''));
	}


	public function testEndsWith()
	{
		$this->assertTrue(Str::endsWith('jason', 'on'));
		$this->assertTrue(Str::endsWith('jason', 'jason'));
		$this->assertTrue(Str::endsWith('jason', array('on')));
		$this->assertFalse(Str::endsWith('jason', 'no'));
		$this->assertFalse(Str::endsWith('jason', array('no')));
		$this->assertFalse(Str::endsWith('jason', ''));
	}


	public function testStrContains()
	{
		$this->assertTrue(Str::contains('allan', 'lla'));
		$this->assertTrue(Str::contains('allan', array('lla')));
		$this->assertFalse(Str::contains('allan', 'xxx'));
		$this->assertFalse(Str::contains('allan', array('xxx')));
		$this->assertFalse(Str::contains('allan', ''));
	}


	public function testParseCallback()
	{
		$this->assertEquals(array('Class', 'method'), Str::parseCallback('Class@method', 'foo'));
		$this->assertEquals(array('Class', 'foo'), Str::parseCallback('Class', 'foo'));
	}


	public function testSlug()
	{
		$this->assertEquals('hello-world', Str::slug('hello world'));
		$this->assertEquals('hello-world', Str::slug('hello-world'));
		$this->assertEquals('hello-world', Str::slug('hello_world'));
		$this->assertEquals('hello_world', Str::slug('hello_world', '_'));
	}


	public function testFinish()
	{
		$this->assertEquals('abbc', Str::finish('ab', 'bc'));
		$this->assertEquals('abbc', Str::finish('abbcbc', 'bc'));
		$this->assertEquals('abcbbc', Str::finish('abcbbcbc', 'bc'));
	}


	public function testIs()
	{
		$this->assertTrue(Str::is('/', '/'));
		$this->assertFalse(Str::is('/', ' /'));
		$this->assertFalse(Str::is('/', '/a'));
		$this->assertTrue(Str::is('foo/*', 'foo/bar/baz'));
		$this->assertTrue(Str::is('*/foo', 'blah/baz/foo'));
	}

}
