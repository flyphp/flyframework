<?php

use Fly\Tests\Factory;
use Fly\Tests\DataStore;
use Mockery as m;

class FactoryTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        m::mock('Post');
        $this->mockedDb = m::mock('\Fly\Database\DatabaseManager');
    }

    public function tearDown()
    {
        m::close();
    }

    public function testCanCreateFactory()
    {
        $factory = m::mock('Fly\Tests\Factory', array($this->mockedDb, new DataStore))->makePartial();

        $factory->shouldReceive('getColumns')
                ->andReturn(array(
                    'occupation' => 'string',
                    'age'        => 'integer'
                ));

        $factory->shouldReceive('getDataType')->andReturn('string');

        $post = $factory->fire('Post');

        $this->assertInstanceOf('Post', $post);
        $this->assertObjectHasAttribute('occupation', $post);
        $this->assertObjectHasAttribute('age', $post);
        $this->assertInternalType('string', $post->occupation);
        $this->assertInternalType('integer', $post->age);
    }

    public function testCanOverrideDefaults()
    {
        $factory = m::mock('Fly\Tests\Factory', array($this->mockedDb, new DataStore))->makePartial();
        $factory->shouldReceive('getColumns')->andReturn(array('email' => 'sample@example.com'));
        $factory->shouldReceive('getDataType')->andReturn('string');

        $post = $factory->fire('Post', array('email' => 'foo'));
        $this->assertEquals('foo', $post->email);
    }

}
