<?php

use Mockery as m;
use Fly\Database\Orm\Relations\HasOne;

class DatabaseOrmRelationTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testTouchMethodUpdatesRelatedTimestamps()
	{
		$builder = m::mock('Fly\Database\Orm\Builder');
		$parent = m::mock('Fly\Database\Orm\Model');
		$parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
		$builder->shouldReceive('getModel')->andReturn($related = m::mock('StdClass'));
		$builder->shouldReceive('where');
		$relation = new HasOne($builder, $parent, 'foreign_key', 'id');
		$related->shouldReceive('getTable')->andReturn('table');
		$related->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
		$related->shouldReceive('freshTimestampString')->andReturn(new DateTime);
		$builder->shouldReceive('update')->once()->with(array('updated_at' => new DateTime));

		$relation->touch();
	}

}

class OrmRelationResetModelStub extends Fly\Database\Orm\Model {}


class OrmRelationResetStub extends Fly\Database\Orm\Builder {
	public function __construct() { $this->query = new OrmRelationQueryStub; }
	public function getModel() { return new OrmRelationResetModelStub; }
}


class OrmRelationQueryStub extends Fly\Database\Query\Builder {
	public function __construct() {}
}