<?php

use Mockery as m;
use Fly\Database\Orm\Collection;

class DatabaseOrmCollectionTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testAddingItemsToCollection()
	{
		$c = new Collection(array('foo'));
		$c->add('bar')->add('baz');
		$this->assertEquals(array('foo', 'bar', 'baz'), $c->all());
	}


	public function testGettingMaxItemsFromCollection()
	{
		$c = new Collection(array((object) array('foo' => 10), (object) array('foo' => 20)));
		$this->assertEquals(20, $c->max('foo'));
	}


	public function testGettingMinItemsFromCollection()
	{
		$c = new Collection(array((object) array('foo' => 10), (object) array('foo' => 20)));
		$this->assertEquals(10, $c->min('foo'));
	}


	public function testContainsIndicatesIfModelInArray()
	{
		$mockModel = m::mock('Fly\Database\Orm\Model');
		$mockModel->shouldReceive('getKey')->andReturn(1);
		$mockModel2 = m::mock('Fly\Database\Orm\Model');
		$mockModel2->shouldReceive('getKey')->andReturn(2);
		$mockModel3 = m::mock('Fly\Database\Orm\Model');
		$mockModel3->shouldReceive('getKey')->andReturn(3);
		$c = new Collection(array($mockModel, $mockModel2));

		$this->assertTrue($c->contains($mockModel));
		$this->assertTrue($c->contains($mockModel2));
		$this->assertFalse($c->contains($mockModel3));
	}


	public function testContainsIndicatesIfKeyedModelInArray()
	{
		$mockModel = m::mock('Fly\Database\Orm\Model');
		$mockModel->shouldReceive('getKey')->andReturn(1);
		$c = new Collection(array($mockModel));
		$mockModel2 = m::mock('Fly\Database\Orm\Model');
		$mockModel2->shouldReceive('getKey')->andReturn(2);
		$c->add($mockModel2);

		$this->assertTrue($c->contains(1));
		$this->assertTrue($c->contains(2));
		$this->assertFalse($c->contains(3));
	}


	public function testFindMethodFindsModelById()
	{
		$mockModel = m::mock('Fly\Database\Orm\Model');
		$mockModel->shouldReceive('getKey')->andReturn(1);
		$c = new Collection(array($mockModel));

		$this->assertTrue($mockModel === $c->find(1));
		$this->assertTrue('allan' === $c->find(2, 'allan'));
	}


	public function testLoadMethodEagerLoadsGivenRelationships()
	{
		$c = $this->getMock('Fly\Database\Orm\Collection', array('first'), array(array('foo')));
		$mockItem = m::mock('StdClass');
		$c->expects($this->once())->method('first')->will($this->returnValue($mockItem));
		$mockItem->shouldReceive('newQuery')->once()->andReturn($mockItem);
		$mockItem->shouldReceive('with')->with(array('bar', 'baz'))->andReturn($mockItem);
		$mockItem->shouldReceive('eagerLoadRelations')->once()->with(array('foo'))->andReturn(array('results'));
		$c->load('bar', 'baz');

		$this->assertEquals(array('results'), $c->all());
	}


	public function testCollectionDictionaryReturnsModelKeys()
	{
		$one = m::mock('Fly\Database\Orm\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Fly\Database\Orm\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Fly\Database\Orm\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c = new Collection(array($one, $two, $three));

		$this->assertEquals(array(1,2,3), $c->modelKeys());
	}


	public function testCollectionMergesWithGivenCollection()
	{
		$one = m::mock('Fly\Database\Orm\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Fly\Database\Orm\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Fly\Database\Orm\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c1 = new Collection(array($one, $two));
		$c2 = new Collection(array($two, $three));

		$this->assertEquals(new Collection(array($one, $two, $three)), $c1->merge($c2));
	}


	public function testCollectionDiffsWithGivenCollection()
	{
		$one = m::mock('Fly\Database\Orm\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Fly\Database\Orm\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Fly\Database\Orm\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c1 = new Collection(array($one, $two));
		$c2 = new Collection(array($two, $three));

		$this->assertEquals(new Collection(array($one)), $c1->diff($c2));
	}


	public function testCollectionIntersectsWithGivenCollection()
	{
		$one = m::mock('Fly\Database\Orm\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Fly\Database\Orm\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$three = m::mock('Fly\Database\Orm\Model');
		$three->shouldReceive('getKey')->andReturn(3);

		$c1 = new Collection(array($one, $two));
		$c2 = new Collection(array($two, $three));

		$this->assertEquals(new Collection(array($two)), $c1->intersect($c2));
	}


	public function testCollectionReturnsUniqueItems()
	{
		$one = m::mock('Fly\Database\Orm\Model');
		$one->shouldReceive('getKey')->andReturn(1);

		$two = m::mock('Fly\Database\Orm\Model');
		$two->shouldReceive('getKey')->andReturn(2);

		$c = new Collection(array($one, $two, $two));

		$this->assertEquals(new Collection(array($one, $two)), $c->unique());
	}


	public function testLists()
	{
		$data = new Collection(array((object) array('name' => 'allan', 'email' => 'foo'), (object) array('name' => 'dayle', 'email' => 'bar')));
		$this->assertEquals(array('allan' => 'foo', 'dayle' => 'bar'), $data->lists('email', 'name'));
		$this->assertEquals(array('foo', 'bar'), $data->lists('email'));
	}

}