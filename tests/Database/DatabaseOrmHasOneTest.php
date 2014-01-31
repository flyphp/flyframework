<?php

use Mockery as m;
use Fly\Database\Orm\Collection;
use Fly\Database\Orm\Relations\HasOne;

class DatabaseOrmHasOneTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testSaveMethodSetsForeignKeyOnModel()
	{
		$relation = $this->getRelation();
		$mockModel = $this->getMock('Fly\Database\Orm\Model', array('save'));
		$mockModel->expects($this->once())->method('save')->will($this->returnValue(true));
		$result = $relation->save($mockModel);

		$attributes = $result->getAttributes();
		$this->assertEquals(1, $attributes['foreign_key']);
	}


	public function testCreateMethodProperlyCreatesNewModel()
	{
		$relation = $this->getRelation();
		$created = $this->getMock('Fly\Database\Orm\Model', array('save', 'getKey', 'setRawAttributes'));
		$created->expects($this->once())->method('save')->will($this->returnValue(true));
		$relation->getRelated()->shouldReceive('newInstance')->once()->andReturn($created);
		$created->expects($this->once())->method('setRawAttributes')->with($this->equalTo(array('name' => 'taylor', 'foreign_key' => 1)));

		$this->assertEquals($created, $relation->create(array('name' => 'taylor')));
	}


	public function testUpdateMethodUpdatesModelsWithTimestamps()
	{
		$relation = $this->getRelation();
		$relation->getRelated()->shouldReceive('usesTimestamps')->once()->andReturn(true);
		$relation->getRelated()->shouldReceive('freshTimestamp')->once()->andReturn(100);
		$relation->getRelated()->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
		$relation->getQuery()->shouldReceive('update')->once()->with(array('foo' => 'bar', 'updated_at' => 100))->andReturn('results');

		$this->assertEquals('results', $relation->update(array('foo' => 'bar')));
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$model = m::mock('Fly\Database\Orm\Model');
		$model->shouldReceive('setRelation')->once()->with('foo', null);
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('table.foreign_key', array(1, 2));
		$model1 = new OrmHasOneModelStub;
		$model1->id = 1;
		$model2 = new OrmHasOneModelStub;
		$model2->id = 2;
		$relation->addEagerConstraints(array($model1, $model2));
	}


	public function testModelsAreProperlyMatchedToParents()
	{
		$relation = $this->getRelation();

		$result1 = new OrmHasOneModelStub;
		$result1->foreign_key = 1;
		$result2 = new OrmHasOneModelStub;
		$result2->foreign_key = 2;

		$model1 = new OrmHasOneModelStub;
		$model1->id = 1;
		$model2 = new OrmHasOneModelStub;
		$model2->id = 2;
		$model3 = new OrmHasOneModelStub;
		$model3->id = 3;

		$models = $relation->match(array($model1, $model2, $model3), new Collection(array($result1, $result2)), 'foo');

		$this->assertEquals(1, $models[0]->foo->foreign_key);
		$this->assertEquals(2, $models[1]->foo->foreign_key);
		$this->assertNull($models[2]->foo);
	}


	public function testRelationCountQueryCanBeBuilt()
	{
		$relation = $this->getRelation();
		$query = m::mock('Fly\Database\Orm\Builder');
		$query->shouldReceive('select')->once()->with(m::type('Fly\Database\Query\Expression'));
		$relation->getParent()->shouldReceive('getTable')->andReturn('table');
		$query->shouldReceive('where')->once()->with('table.foreign_key', '=', m::type('Fly\Database\Query\Expression'));
		$relation->getParent()->shouldReceive('getQuery')->andReturn($parentQuery = m::mock('StdClass'));
		$parentQuery->shouldReceive('getGrammar')->once()->andReturn($grammar = m::mock('StdClass'));
		$grammar->shouldReceive('wrap')->once()->with('table.id');

		$relation->getRelationCountQuery($query, $query);
	}


	protected function getRelation()
	{
		$builder = m::mock('Fly\Database\Orm\Builder');
		$builder->shouldReceive('where')->with('table.foreign_key', '=', 1);
		$related = m::mock('Fly\Database\Orm\Model');
		$builder->shouldReceive('getModel')->andReturn($related);
		$parent = m::mock('Fly\Database\Orm\Model');
		$parent->shouldReceive('getAttribute')->with('id')->andReturn(1);
		$parent->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
		$parent->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
		return new HasOne($builder, $parent, 'table.foreign_key', 'id');
	}

}

class OrmHasOneModelStub extends Fly\Database\Orm\Model {
	public $foreign_key = 'foreign.value';
}