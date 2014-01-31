<?php

class SupportHelpersTest extends PHPUnit_Framework_TestCase {

	public function testArrayBuild()
	{
		$this->assertEquals(array('foo' => 'bar'), array_build(array('foo' => 'bar'), function($key, $value)
		{
			return array($key, $value);
		}));
	}


	public function testArrayDot()
	{
		$array = array_dot(array('name' => 'allan', 'languages' => array('php' => true)));
		$this->assertEquals($array, array('name' => 'allan', 'languages.php' => true));
	}


	public function testArrayGet()
	{
		$array = array('names' => array('developer' => 'allan'));
		$this->assertEquals('allan', array_get($array, 'names.developer'));
		$this->assertEquals('dayle', array_get($array, 'names.otherDeveloper', 'dayle'));
		$this->assertEquals('dayle', array_get($array, 'names.otherDeveloper', function() { return 'dayle'; }));
	}


	public function testArraySet()
	{
		$array = array();
		array_set($array, 'names.developer', 'allan');
		$this->assertEquals('allan', $array['names']['developer']);
	}


	public function testArrayForget()
	{
		$array = array('names' => array('developer' => 'allan', 'otherDeveloper' => 'dayle'));
		array_forget($array, 'names.developer');
		$this->assertFalse(isset($array['names']['developer']));
		$this->assertTrue(isset($array['names']['otherDeveloper']));
	}


	public function testArrayPluckWithArrayAndObjectValues()
	{
		$array = array((object) array('name' => 'allan', 'email' => 'foo'), array('name' => 'dayle', 'email' => 'bar'));
		$this->assertEquals(array('allan', 'dayle'), array_pluck($array, 'name'));
		$this->assertEquals(array('allan' => 'foo', 'dayle' => 'bar'), array_pluck($array, 'email', 'name'));
	}


	public function testArrayExcept()
	{
		$array = array('name' => 'allan', 'age' => 26);
		$this->assertEquals(array('age' => 26), array_except($array, array('name')));
	}


	public function testArrayOnly()
	{
		$array = array('name' => 'allan', 'age' => 26);
		$this->assertEquals(array('name' => 'allan'), array_only($array, array('name')));
	}


	public function testArrayDivide()
	{
		$array = array('name' => 'allan');
		list($keys, $values) = array_divide($array);
		$this->assertEquals(array('name'), $keys);
		$this->assertEquals(array('allan'), $values);
	}


	public function testArrayFirst()
	{
		$array = array('name' => 'allan', 'otherDeveloper' => 'dayle');
		$this->assertEquals('dayle', array_first($array, function($key, $value) { return $value == 'dayle'; }));
	}


	public function testArrayFetch()
	{
		$data = array(
			'post-1' => array(
				'comments' => array(
					'tags' => array(
						'#foo', '#bar',
					),
				),
			),
			'post-2' => array(
				'comments' => array(
					'tags' => array(
						'#baz',
					),
				),
			),
		);

		$this->assertEquals(array(
			0 => array(
				'tags' => array(
					'#foo', '#bar',
				),
			),
			1 => array(
				'tags' => array(
					'#baz',
				),
			),
		), array_fetch($data, 'comments'));

		$this->assertEquals(array(array('#foo', '#bar'), array('#baz')), array_fetch($data, 'comments.tags'));
	}


	public function testArrayFlatten()
	{
		$this->assertEquals(array('#foo', '#bar', '#baz'), array_flatten(array(array('#foo', '#bar'), array('#baz'))));
	}


	public function testStrIs()
	{
		$this->assertTrue(str_is('*.dev', 'localhost.dev'));
		$this->assertTrue(str_is('a', 'a'));
		$this->assertTrue(str_is('/', '/'));
		$this->assertTrue(str_is('*dev*', 'localhost.dev'));
		$this->assertTrue(str_is('foo?bar', 'foo?bar'));
		$this->assertFalse(str_is('*something', 'foobar'));
		$this->assertFalse(str_is('foo', 'bar'));
		$this->assertFalse(str_is('foo.*', 'foobar'));
		$this->assertFalse(str_is('foo.ar', 'foobar'));
		$this->assertFalse(str_is('foo?bar', 'foobar'));
		$this->assertFalse(str_is('foo?bar', 'fobar'));
	}


	public function testStartsWith()
	{
		$this->assertTrue(starts_with('jason', 'jas'));
		$this->assertTrue(starts_with('jason', array('jas')));
		$this->assertFalse(starts_with('jason', 'day'));
		$this->assertFalse(starts_with('jason', array('day')));
	}


	public function testEndsWith()
	{
		$this->assertTrue(ends_with('jason', 'on'));
		$this->assertTrue(ends_with('jason', array('on')));
		$this->assertFalse(ends_with('jason', 'no'));
		$this->assertFalse(ends_with('jason', array('no')));
	}


	public function testStrContains()
	{
		$this->assertTrue(str_contains('allan', 'lla'));
		$this->assertTrue(str_contains('allan', array('lla')));
		$this->assertFalse(str_contains('allan', 'xxx'));
		$this->assertFalse(str_contains('allan', array('xxx')));
	}


	public function testSnakeCase()
	{
		$this->assertEquals('foo_bar', snake_case('fooBar'));
	}


	public function testCamelCase()
	{
		$this->assertEquals('fooBar', camel_case('FooBar'));
		$this->assertEquals('fooBar', camel_case('foo_bar'));
		$this->assertEquals('fooBarBaz', camel_case('Foo-barBaz'));
		$this->assertEquals('fooBarBaz', camel_case('foo-bar_baz'));
	}


	public function testStudlyCase()
	{
		$this->assertEquals('FooBar', studly_case('fooBar'));
		$this->assertEquals('FooBar', studly_case('foo_bar'));
		$this->assertEquals('FooBarBaz', studly_case('foo-barBaz'));
		$this->assertEquals('FooBarBaz', studly_case('foo-bar_baz'));
	}


	public function testValue()
	{
		$this->assertEquals('foo', value('foo'));
		$this->assertEquals('foo', value(function() { return 'foo'; }));
	}


	public function testObjectGet()
	{
		$class = new StdClass;
		$class->name = new StdClass;
		$class->name->first = 'Allan';

		$this->assertEquals('Allan', object_get($class, 'name.first'));
	}


	public function testArraySort()
	{
		$array = array(
			array('name' => 'baz'),
			array('name' => 'foo'),
			array('name' => 'bar'),
		);

		$this->assertEquals(array(
			array('name' => 'bar'),
			array('name' => 'baz'),
			array('name' => 'foo')),
		array_values(array_sort($array, function($v) { return $v['name']; })));
	}

}
