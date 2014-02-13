<?php

use Mockery as m;
use Fly\Database\ActiveRecord\Relations\HasOne;

class DatabaseActiveRecordRelationTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testTouchMethodUpdatesRelatedTimestamps()
	{
		$builder = m::mock('Fly\Database\ActiveRecord\Builder');
		$parent = m::mock('Fly\Database\ActiveRecord\Model');
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

class ActiveRecordRelationResetModelStub extends Fly\Database\ActiveRecord\Model {}


class ActiveRecordRelationResetStub extends Fly\Database\ActiveRecord\Builder {
	public function __construct() { $this->query = new ActiveRecordRelationQueryStub; }
	public function getModel() { return new ActiveRecordRelationResetModelStub; }
}


class ActiveRecordRelationQueryStub extends Fly\Database\Query\Builder {
	public function __construct() {}
}