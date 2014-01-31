<?php

use Mockery as m;
use Fly\Database\Orm\Collection;
use Fly\Database\Orm\Relations\HasManyThrough;

class DatabaseOrmHasManyThroughTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRelationIsProperlyInitialized()
	{
		$relation = $this->getRelation();
		$model = m::mock('Fly\Database\Orm\Model');
		$relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function($array = array()) { return new Collection($array); });
		$model->shouldReceive('setRelation')->once()->with('foo', m::type('Fly\Database\Orm\Collection'));
		$models = $relation->initRelation(array($model), 'foo');

		$this->assertEquals(array($model), $models);
	}


	public function testEagerConstraintsAreProperlyAdded()
	{
		$relation = $this->getRelation();
		$relation->getQuery()->shouldReceive('whereIn')->once()->with('users.country_id', array(1, 2));
		$model1 = new OrmHasManyThroughModelStub;
		$model1->id = 1;
		$model2 = new OrmHasManyThroughModelStub;
		$model2->id = 2;
		$relation->addEagerConstraints(array($model1, $model2));
	}


	public function testModelsAreProperlyMatchedToParents()
	{
		$relation = $this->getRelation();

		$result1 = new OrmHasManyThroughModelStub;
		$result1->country_id = 1;
		$result2 = new OrmHasManyThroughModelStub;
		$result2->country_id = 2;
		$result3 = new OrmHasManyThroughModelStub;
		$result3->country_id = 2;

		$model1 = new OrmHasManyThroughModelStub;
		$model1->id = 1;
		$model2 = new OrmHasManyThroughModelStub;
		$model2->id = 2;
		$model3 = new OrmHasManyThroughModelStub;
		$model3->id = 3;

		$relation->getRelated()->shouldReceive('newCollection')->andReturnUsing(function($array) { return new Collection($array); });
		$models = $relation->match(array($model1, $model2, $model3), new Collection(array($result1, $result2, $result3)), 'foo');

		$this->assertEquals(1, $models[0]->foo[0]->country_id);
		$this->assertEquals(1, count($models[0]->foo));
		$this->assertEquals(2, $models[1]->foo[0]->country_id);
		$this->assertEquals(2, $models[1]->foo[1]->country_id);
		$this->assertEquals(2, count($models[1]->foo));
		$this->assertEquals(0, count($models[2]->foo));
	}


	protected function getRelation()
	{
		$builder = m::mock('Fly\Database\Orm\Builder');
		$builder->shouldReceive('join')->once()->with('users', 'users.id', '=', 'posts.user_id');
		$builder->shouldReceive('where')->with('users.country_id', '=', 1);

		$country = m::mock('Fly\Database\Orm\Model');
		$country->shouldReceive('getKey')->andReturn(1);
		$country->shouldReceive('getForeignKey')->andReturn('country_id');
		$user = m::mock('Fly\Database\Orm\Model');
		$user->shouldReceive('getTable')->andReturn('users');
		$user->shouldReceive('getQualifiedKeyName')->andReturn('users.id');
		$post = m::mock('Fly\Database\Orm\Model');
		$post->shouldReceive('getTable')->andReturn('posts');

		$builder->shouldReceive('getModel')->andReturn($post);

		$user->shouldReceive('getKey')->andReturn(1);
		$user->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
		$user->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');
		return new HasManyThrough($builder, $country, $user, 'country_id', 'user_id');
	}

}

class OrmHasManyThroughModelStub extends Fly\Database\Orm\Model {
	public $country_id = 'foreign.value';
}