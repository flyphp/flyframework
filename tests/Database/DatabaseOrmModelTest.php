<?php

use Mockery as m;

class DatabaseOrmModelTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();

		Fly\Database\Orm\Model::unsetEventDispatcher();
	}


	public function testAttributeManipulation()
	{
		$model = new OrmModelStub;
		$model->name = 'foo';
		$this->assertEquals('foo', $model->name);
		$this->assertTrue(isset($model->name));
		unset($model->name);
		$this->assertFalse(isset($model->name));

		// test mutation
		$model->list_items = array('name' => 'allan');
		$this->assertEquals(array('name' => 'allan'), $model->list_items);
		$attributes = $model->getAttributes();
		$this->assertEquals(json_encode(array('name' => 'allan')), $attributes['list_items']);
	}


	public function testCalculatedAttributes()
	{
		$model = new OrmModelStub;
		$model->password = 'secret';
		$attributes = $model->getAttributes();

		// ensure password attribute was not set to null
		$this->assertFalse(array_key_exists('password', $attributes));
		$this->assertEquals('******', $model->password);
		$this->assertEquals('5ebe2294ecd0e0f08eab7690d2a6ee69', $attributes['password_hash']);
		$this->assertEquals('5ebe2294ecd0e0f08eab7690d2a6ee69', $model->password_hash);
	}


	public function testNewInstanceReturnsNewInstanceWithAttributesSet()
	{
		$model = new OrmModelStub;
		$instance = $model->newInstance(array('name' => 'allan'));
		$this->assertInstanceOf('OrmModelStub', $instance);
		$this->assertEquals('allan', $instance->name);
	}


	public function testCreateMethodSavesNewModel()
	{
		$_SERVER['__orm.saved'] = false;
		$model = OrmModelSaveStub::create(array('name' => 'allan'));
		$this->assertTrue($_SERVER['__orm.saved']);
		$this->assertEquals('allan', $model->name);
	}


	public function testFindMethodCallsQueryBuilderCorrectly()
	{
		$result = OrmModelFindStub::find(1);
		$this->assertEquals('foo', $result);
	}


	public function testFindMethodWithArrayCallsQueryBuilderCorrectly()
	{
		$result = OrmModelFindManyStub::find(array(1, 2));
		$this->assertEquals('foo', $result);
	}


	public function testDestroyMethodCallsQueryBuilderCorrectly()
	{
		$result = OrmModelDestroyStub::destroy(1, 2, 3);
	}


	public function testWithMethodCallsQueryBuilderCorrectly()
	{
		$result = OrmModelWithStub::with('foo', 'bar');
		$this->assertEquals('foo', $result);
	}


	public function testWithMethodCallsQueryBuilderCorrectlyWithArray()
	{
		$result = OrmModelWithStub::with(array('foo', 'bar'));
		$this->assertEquals('foo', $result);
	}


	public function testUpdateProcess()
	{
		$model = $this->getMock('OrmModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Fly\Database\Orm\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('name' => 'allan'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('orm.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('orm.updating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('orm.updated: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('orm.saved: '.get_class($model), $model)->andReturn(true);

		$model->id = 1;
		$model->foo = 'bar';
		// make sure foo isn't synced so we can test that dirty attributes only are updated
		$model->syncOriginal();
		$model->name = 'allan';
		$model->exists = true;
		$this->assertTrue($model->save());
	}


	public function testUpdateProcessDoesntOverrideTimestamps()
	{
		$model = $this->getMock('OrmModelStub', array('newQuery'));
		$query = m::mock('Fly\Database\Orm\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('created_at' => 'foo', 'updated_at' => 'bar'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until');
		$events->shouldReceive('fire');

		$model->id = 1;
		$model->syncOriginal();
		$model->created_at = 'foo';
		$model->updated_at = 'bar';
		$model->exists = true;
		$this->assertTrue($model->save());
	}


	public function testSaveIsCancelledIfSavingEventReturnsFalse()
	{
		$model = $this->getMock('OrmModelStub', array('newQuery'));
		$query = m::mock('Fly\Database\Orm\Builder');
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('orm.saving: '.get_class($model), $model)->andReturn(false);
		$model->exists = true;

		$this->assertFalse($model->save());
	}


	public function testUpdateIsCancelledIfUpdatingEventReturnsFalse()
	{
		$model = $this->getMock('OrmModelStub', array('newQuery'));
		$query = m::mock('Fly\Database\Orm\Builder');
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('orm.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('orm.updating: '.get_class($model), $model)->andReturn(false);
		$model->exists = true;
		$model->foo = 'bar';

		$this->assertFalse($model->save());
	}


	public function testUpdateProcessWithoutTimestamps()
	{
		$model = $this->getMock('OrmModelStub', array('newQuery', 'updateTimestamps', 'fireModelEvent'));
		$model->timestamps = false;
		$query = m::mock('Fly\Database\Orm\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('name' => 'allan'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->never())->method('updateTimestamps');
		$model->expects($this->any())->method('fireModelEvent')->will($this->returnValue(true));

		$model->id = 1;
		$model->syncOriginal();
		$model->name = 'allan';
		$model->exists = true;
		$this->assertTrue($model->save());
	}


	public function testUpdateUsesOldPrimaryKey()
	{
		$model = $this->getMock('OrmModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Fly\Database\Orm\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('id' => 2, 'foo' => 'bar'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('orm.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('orm.updating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('orm.updated: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('orm.saved: '.get_class($model), $model)->andReturn(true);

		$model->id = 1;
		$model->syncOriginal();
		$model->id = 2;
		$model->foo = 'bar';
		$model->exists = true;

		$this->assertTrue($model->save());
	}


	public function testTimestampsAreReturnedAsObjects()
	{
		$model = $this->getMock('OrmDateModelStub', array('getDateFormat'));
		$model->expects($this->any())->method('getDateFormat')->will($this->returnValue('Y-m-d'));
		$model->setRawAttributes(array(
			'created_at'	=> '2012-12-04',
			'updated_at'	=> '2012-12-05',
		));

		$this->assertInstanceOf('Carbon\Carbon', $model->created_at);
		$this->assertInstanceOf('Carbon\Carbon', $model->updated_at);
	}


	public function testTimestampsAreReturnedAsObjectsFromPlainDatesAndTimestamps()
	{
		$model = $this->getMock('OrmDateModelStub', array('getDateFormat'));
		$model->expects($this->any())->method('getDateFormat')->will($this->returnValue('Y-m-d H:i:s'));
		$model->setRawAttributes(array(
			'created_at'	=> '2012-12-04',
			'updated_at'	=> time(),
		));

		$this->assertInstanceOf('Carbon\Carbon', $model->created_at);
		$this->assertInstanceOf('Carbon\Carbon', $model->updated_at);
	}


	public function testTimestampsAreReturnedAsObjectsOnCreate()
	{
		$timestamps = array(
			'created_at' => new DateTime,
			'updated_at' => new DateTime
		);
		$model = new OrmDateModelStub;
		Fly\Database\Orm\Model::setConnectionResolver($resolver = m::mock('Fly\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock('StdClass'));
		$mockConnection->shouldReceive('getQueryGrammar')->andReturn($mockConnection);
		$mockConnection->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
		$instance = $model->newInstance($timestamps);
		$this->assertInstanceOf('Carbon\Carbon', $instance->updated_at);
		$this->assertInstanceOf('Carbon\Carbon', $instance->created_at);
	}


	public function testDateTimeAttributesReturnNullIfSetToNull()
	{
		$timestamps = array(
			'created_at' => new DateTime,
			'updated_at' => new DateTime
		);
		$model = new OrmDateModelStub;
		Fly\Database\Orm\Model::setConnectionResolver($resolver = m::mock('Fly\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock('StdClass'));
		$mockConnection->shouldReceive('getQueryGrammar')->andReturn($mockConnection);
		$mockConnection->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
		$instance = $model->newInstance($timestamps);

		$instance->created_at = null;
		$this->assertNull($instance->created_at);
	}


	public function testTimestampsAreCreatedFromStringsAndIntegers()
	{
		$model = new OrmDateModelStub;
		$model->created_at = '2013-05-22 00:00:00';
		$this->assertInstanceOf('Carbon\Carbon', $model->created_at);

		$model = new OrmDateModelStub;
		$model->created_at = time();
		$this->assertInstanceOf('Carbon\Carbon', $model->created_at);

		$model = new OrmDateModelStub;
		$model->created_at = '2012-01-01';
		$this->assertInstanceOf('Carbon\Carbon', $model->created_at);
	}


	public function testInsertProcess()
	{
		$model = $this->getMock('OrmModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Fly\Database\Orm\Builder');
		$query->shouldReceive('insertGetId')->once()->with(array('name' => 'allan'), 'id')->andReturn(1);
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');

		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('orm.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('orm.creating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('orm.created: '.get_class($model), $model);
		$events->shouldReceive('fire')->once()->with('orm.saved: '.get_class($model), $model);

		$model->name = 'allan';
		$model->exists = false;
		$this->assertTrue($model->save());
		$this->assertEquals(1, $model->id);
		$this->assertTrue($model->exists);

		$model = $this->getMock('OrmModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Fly\Database\Orm\Builder');
		$query->shouldReceive('insert')->once()->with(array('name' => 'allan'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');
		$model->setIncrementing(false);

		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('orm.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('orm.creating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('orm.created: '.get_class($model), $model);
		$events->shouldReceive('fire')->once()->with('orm.saved: '.get_class($model), $model);

		$model->name = 'allan';
		$model->exists = false;
		$this->assertTrue($model->save());
		$this->assertNull($model->id);
		$this->assertTrue($model->exists);
	}


	public function testInsertIsCancelledIfCreatingEventReturnsFalse()
	{
		$model = $this->getMock('OrmModelStub', array('newQuery'));
		$query = m::mock('Fly\Database\Orm\Builder');
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('orm.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('orm.creating: '.get_class($model), $model)->andReturn(false);

		$this->assertFalse($model->save());
		$this->assertFalse($model->exists);
	}


	public function testDeleteProperlyDeletesModel()
	{
		$model = $this->getMock('Fly\Database\Orm\Model', array('newQuery', 'updateTimestamps', 'touchOwners'));
		$query = m::mock('stdClass');
		$query->shouldReceive('where')->once()->with('id', 1)->andReturn($query);
		$query->shouldReceive('delete')->once();
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('touchOwners');
		$model->exists = true;
		$model->id = 1;
		$model->delete();
	}


	public function testDeleteProperlyDeletesModelWhenSoftDeleting()
	{
		$model = $this->getMock('Fly\Database\Orm\Model', array('newQuery', 'updateTimestamps', 'touchOwners'));
		$model->setSoftDeleting(true);
		$query = m::mock('stdClass');
		$query->shouldReceive('where')->once()->with('id', 1)->andReturn($query);
		$query->shouldReceive('update')->once()->with(array('deleted_at' => $model->fromDateTime(new DateTime)));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('touchOwners');
		$model->exists = true;
		$model->id = 1;
		$model->delete();
	}


	public function testRestoreProperlyRestoresModel()
	{
		$model = $this->getMock('Fly\Database\Orm\Model', array('save'));
		$model->setSoftDeleting(true);
		$model->expects($this->once())->method('save');
		$model->restore();

		$this->assertNull($model->deleted_at);
	}


	public function testNewQueryReturnsOrmQueryBuilder()
	{
		$conn = m::mock('Fly\Database\Connection');
		$grammar = m::mock('Fly\Database\Query\Grammars\Grammar');
		$processor = m::mock('Fly\Database\Query\Processors\Processor');
		$conn->shouldReceive('getQueryGrammar')->once()->andReturn($grammar);
		$conn->shouldReceive('getPostProcessor')->once()->andReturn($processor);
		OrmModelStub::setConnectionResolver($resolver = m::mock('Fly\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn($conn);
		$model = new OrmModelStub;
		$builder = $model->newQuery();
		$this->assertInstanceOf('Fly\Database\Orm\Builder', $builder);
	}


	public function testGetAndSetTableOperations()
	{
		$model = new OrmModelStub;
		$this->assertEquals('stub', $model->getTable());
		$model->setTable('foo');
		$this->assertEquals('foo', $model->getTable());
	}


	public function testGetKeyReturnsValueOfPrimaryKey()
	{
		$model = new OrmModelStub;
		$model->id = 1;
		$this->assertEquals(1, $model->getKey());
		$this->assertEquals('id', $model->getKeyName());
	}


	public function testConnectionManagement()
	{
		OrmModelStub::setConnectionResolver($resolver = m::mock('Fly\Database\ConnectionResolverInterface'));
		$model = new OrmModelStub;
		$model->setConnection('foo');
		$resolver->shouldReceive('connection')->once()->with('foo')->andReturn('bar');

		$this->assertEquals('bar', $model->getConnection());
	}


	public function testToArray()
	{
		$model = new OrmModelStub;
		$model->name = 'foo';
		$model->age = null;
		$model->password = 'password1';
		$model->setHidden(array('password'));
		$model->setRelation('names', new Fly\Database\Orm\Collection(array(
			new OrmModelStub(array('bar' => 'baz')), new OrmModelStub(array('bam' => 'boom'))
		)));
		$model->setRelation('partner', new OrmModelStub(array('name' => 'abby')));
		$model->setRelation('group', null);
		$model->setRelation('multi', new Fly\Database\Orm\Collection);
		$array = $model->toArray();

		$this->assertTrue(is_array($array));
		$this->assertEquals('foo', $array['name']);
		$this->assertEquals('baz', $array['names'][0]['bar']);
		$this->assertEquals('boom', $array['names'][1]['bam']);
		$this->assertEquals('abby', $array['partner']['name']);
		$this->assertEquals(null, $array['group']);
		$this->assertEquals(array(), $array['multi']);
		$this->assertFalse(isset($array['password']));

		$model->setAppends(array('appendable'));
		$array = $model->toArray();
		$this->assertEquals('appended', $array['appendable']);
	}


	public function testVisibleCreatesArrayWhitelist()
	{
		$model = new OrmModelStub;
		$model->setVisible(array('name'));
		$model->name = 'Allan';
		$model->age = 26;
		$array = $model->toArray();

		$this->assertEquals(array('name' => 'Allan'), $array);
	}


	public function testHiddenCanAlsoExcludeRelationships()
	{
		$model = new OrmModelStub;
		$model->name = 'Allan';
		$model->setRelation('foo', array('bar'));
		$model->setHidden(array('foo', 'list_items', 'password'));
		$array = $model->toArray();

		$this->assertEquals(array('name' => 'Allan'), $array);
	}


	public function testToArraySnakeAttributes()
	{
		$model = new OrmModelStub;
		$model->setRelation('namesList', new Fly\Database\Orm\Collection(array(
			new OrmModelStub(array('bar' => 'baz')), new OrmModelStub(array('bam' => 'boom'))
		)));
		$array = $model->toArray();

		$this->assertEquals('baz', $array['names_list'][0]['bar']);
		$this->assertEquals('boom', $array['names_list'][1]['bam']);

		$model = new OrmModelCamelStub;
		$model->setRelation('namesList', new Fly\Database\Orm\Collection(array(
			new OrmModelStub(array('bar' => 'baz')), new OrmModelStub(array('bam' => 'boom'))
		)));
		$array = $model->toArray();

		$this->assertEquals('baz', $array['namesList'][0]['bar']);
		$this->assertEquals('boom', $array['namesList'][1]['bam']);
	}


	public function testToArrayUsesMutators()
	{
		$model = new OrmModelStub;
		$model->list_items = array(1, 2, 3);
		$array = $model->toArray();

		$this->assertEquals(array(1, 2, 3), $array['list_items']);
	}


	public function testFillable()
	{
		$model = new OrmModelStub;
		$model->fillable(array('name', 'age'));
		$model->fill(array('name' => 'foo', 'age' => 'bar'));
		$this->assertEquals('foo', $model->name);
		$this->assertEquals('bar', $model->age);
	}


	public function testUnguardAllowsAnythingToBeSet()
	{
		$model = new OrmModelStub;
		OrmModelStub::unguard();
		$model->guard(array('*'));
		$model->fill(array('name' => 'foo', 'age' => 'bar'));
		$this->assertEquals('foo', $model->name);
		$this->assertEquals('bar', $model->age);
		OrmModelStub::setUnguardState(false);
	}


	public function testUnderscorePropertiesAreNotFilled()
	{
		$model = new OrmModelStub;
		$model->fill(array('_method' => 'PUT'));
		$this->assertEquals(array(), $model->getAttributes());
	}


	public function testGuarded()
	{
		$model = new OrmModelStub;
		$model->guard(array('name', 'age'));
		$model->fill(array('name' => 'foo', 'age' => 'bar', 'foo' => 'bar'));
		$this->assertFalse(isset($model->name));
		$this->assertFalse(isset($model->age));
		$this->assertEquals('bar', $model->foo);
	}


	public function testFillableOverridesGuarded()
	{
		$model = new OrmModelStub;
		$model->guard(array('name', 'age'));
		$model->fillable(array('age', 'foo'));
		$model->fill(array('name' => 'foo', 'age' => 'bar', 'foo' => 'bar'));
		$this->assertFalse(isset($model->name));
		$this->assertEquals('bar', $model->age);
		$this->assertEquals('bar', $model->foo);
	}


	/**
	 * @expectedException Fly\Database\Orm\MassAssignmentException
	 */
	public function testGlobalGuarded()
	{
		$model = new OrmModelStub;
		$model->guard(array('*'));
		$model->fill(array('name' => 'foo', 'age' => 'bar', 'votes' => 'baz'));
	}


	public function testHasOneCreatesProperRelation()
	{
		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasOne('OrmModelSaveStub');
		$this->assertEquals('save_stub.orm_model_stub_id', $relation->getForeignKey());

		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasOne('OrmModelSaveStub', 'foo');
		$this->assertEquals('save_stub.foo', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof OrmModelSaveStub);
	}


	public function testMorphOneCreatesProperRelation()
	{
		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->morphOne('OrmModelSaveStub', 'morph');
		$this->assertEquals('save_stub.morph_id', $relation->getForeignKey());
		$this->assertEquals('save_stub.morph_type', $relation->getMorphType());
		$this->assertEquals('OrmModelStub', $relation->getMorphClass());
	}


	public function testHasManyCreatesProperRelation()
	{
		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasMany('OrmModelSaveStub');
		$this->assertEquals('save_stub.orm_model_stub_id', $relation->getForeignKey());

		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasMany('OrmModelSaveStub', 'foo');
		$this->assertEquals('save_stub.foo', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof OrmModelSaveStub);
	}


	public function testMorphManyCreatesProperRelation()
	{
		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->morphMany('OrmModelSaveStub', 'morph');
		$this->assertEquals('save_stub.morph_id', $relation->getForeignKey());
		$this->assertEquals('save_stub.morph_type', $relation->getMorphType());
		$this->assertEquals('OrmModelStub', $relation->getMorphClass());
	}


	public function testBelongsToCreatesProperRelation()
	{
		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToStub();
		$this->assertEquals('belongs_to_stub_id', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof OrmModelSaveStub);

		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToExplicitKeyStub();
		$this->assertEquals('foo', $relation->getForeignKey());
	}


	public function testMorphToCreatesProperRelation()
	{
		$model = m::mock('Fly\Database\Orm\Model[belongsTo]');
		$model->foo_type = 'FooClass';
		$model->shouldReceive('belongsTo')->with('FooClass', 'foo_id');
		$relation = $model->morphTo('foo');

		$model = m::mock('OrmModelStub[belongsTo]');
		$model->morph_to_stub_type = 'FooClass';
		$model->shouldReceive('belongsTo')->with('FooClass', 'morph_to_stub_id');
		$relation = $model->morphToStub();
	}


	public function testBelongsToManyCreatesProperRelation()
	{
		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToMany('OrmModelSaveStub');
		$this->assertEquals('orm_model_save_stub_orm_model_stub.orm_model_stub_id', $relation->getForeignKey());
		$this->assertEquals('orm_model_save_stub_orm_model_stub.orm_model_save_stub_id', $relation->getOtherKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof OrmModelSaveStub);

		$model = new OrmModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToMany('OrmModelSaveStub', 'table', 'foreign', 'other');
		$this->assertEquals('table.foreign', $relation->getForeignKey());
		$this->assertEquals('table.other', $relation->getOtherKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof OrmModelSaveStub);
	}


	public function testModelsAssumeTheirName()
	{
		$model = new OrmModelWithoutTableStub;
		$this->assertEquals('orm_model_without_table_stubs', $model->getTable());

		require_once __DIR__.'/stubs/OrmModelNamespacedStub.php';
		$namespacedModel = new Foo\Bar\OrmModelNamespacedStub;
		$this->assertEquals('orm_model_namespaced_stubs', $namespacedModel->getTable());
	}


	public function testTheMutatorCacheIsPopulated()
	{
		$class = new OrmModelStub;

		$this->assertEquals(array('list_items', 'password', 'appendable'), $class->getMutatedAttributes());
	}


	public function testCloneModelMakesAFreshCopyOfTheModel()
	{
		$class = new OrmModelStub;
		$class->id = 1;
		$class->exists = true;
		$class->first = 'allan';
		$class->last = 'freitas';
		$class->setRelation('foo', array('bar'));

		$clone = $class->replicate();

		$this->assertNull($clone->id);
		$this->assertFalse($clone->exists);
		$this->assertEquals('allan', $clone->first);
		$this->assertEquals('freitas', $clone->last);
		$this->assertEquals(array('bar'), $clone->foo);
	}


	public function testModelObserversCanBeAttachedToModels()
	{
		OrmModelStub::setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('listen')->once()->with('orm.creating: OrmModelStub', 'OrmTestObserverStub@creating');
		$events->shouldReceive('listen')->once()->with('orm.saved: OrmModelStub', 'OrmTestObserverStub@saved');
		$events->shouldReceive('forget');
		OrmModelStub::observe(new OrmTestObserverStub);
		OrmModelStub::flushEventListeners();
	}


	/**
	 * @expectedException LogicException
	 */
	public function testGetModelAttributeMethodThrowsExceptionIfNotRelation()
	{
		$model = new OrmModelStub;
		$relation = $model->incorrect_relation_stub;
	}


	protected function addMockConnection($model)
	{
		$model->setConnectionResolver($resolver = m::mock('Fly\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn(m::mock('Fly\Database\Connection'));
		$model->getConnection()->shouldReceive('getQueryGrammar')->andReturn(m::mock('Fly\Database\Query\Grammars\Grammar'));
		$model->getConnection()->shouldReceive('getPostProcessor')->andReturn(m::mock('Fly\Database\Query\Processors\Processor'));
	}

}

class OrmTestObserverStub {
	public function creating() {}
	public function saved() {}
}
class OrmModelStub extends Fly\Database\Orm\Model {
	protected $table = 'stub';
	protected $guarded = array();
	public function getListItemsAttribute($value)
	{
		return json_decode($value, true);
	}
	public function setListItemsAttribute($value)
	{
		$this->attributes['list_items'] = json_encode($value);
	}
	public function getPasswordAttribute()
	{
		return '******';
	}
	public function setPasswordAttribute($value)
	{
		$this->attributes['password_hash'] = md5($value);
	}
	public function belongsToStub()
	{
		return $this->belongsTo('OrmModelSaveStub');
	}
	public function morphToStub()
	{
		return $this->morphTo();
	}
	public function belongsToExplicitKeyStub()
	{
		return $this->belongsTo('OrmModelSaveStub', 'foo');
	}
	public function incorrectRelationStub()
	{
		return 'foo';
	}
	public function getDates()
	{
		return array();
	}
	public function getAppendableAttribute()
	{
		return 'appended';
	}
}

class OrmModelCamelStub extends OrmModelStub {
	public static $snakeAttributes = false;
}

class OrmDateModelStub extends OrmModelStub {
	public function getDates()
	{
		return array('created_at', 'updated_at');
	}
}

class OrmModelSaveStub extends Fly\Database\Orm\Model {
	protected $table = 'save_stub';
	protected $guarded = array();
	public function save(array $options = array()) { $_SERVER['__orm.saved'] = true; }
	public function setIncrementing($value)
	{
		$this->incrementing = $value;
	}
}

class OrmModelFindStub extends Fly\Database\Orm\Model {
	public function newQuery($excludeDeleted = true)
	{
		$mock = m::mock('Fly\Database\Orm\Builder');
		$mock->shouldReceive('find')->once()->with(1, array('*'))->andReturn('foo');
		return $mock;
	}
}

class OrmModelDestroyStub extends Fly\Database\Orm\Model {
	public function newQuery($excludeDeleted = true)
	{
		$mock = m::mock('Fly\Database\Orm\Builder');
		$mock->shouldReceive('whereIn')->once()->with('id', array(1, 2, 3))->andReturn($mock);
		$mock->shouldReceive('get')->once()->andReturn(array($model = m::mock('StdClass')));
		$model->shouldReceive('delete')->once();
		return $mock;
	}
}

class OrmModelFindManyStub extends Fly\Database\Orm\Model {
	public function newQuery($excludeDeleted = true)
	{
		$mock = m::mock('Fly\Database\Orm\Builder');
		$mock->shouldReceive('find')->once()->with(array(1, 2), array('*'))->andReturn('foo');
		return $mock;
	}
}

class OrmModelWithStub extends Fly\Database\Orm\Model {
	public function newQuery($excludeDeleted = true)
	{
		$mock = m::mock('Fly\Database\Orm\Builder');
		$mock->shouldReceive('with')->once()->with(array('foo', 'bar'))->andReturn('foo');
		return $mock;
	}
}

class OrmModelWithoutTableStub extends Fly\Database\Orm\Model {}
