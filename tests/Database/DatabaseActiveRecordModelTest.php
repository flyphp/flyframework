<?php

use Mockery as m;

class DatabaseActiveRecordModelTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();

		Fly\Database\ActiveRecord\Model::unsetEventDispatcher();
	}


	public function testAttributeManipulation()
	{
		$model = new ActiveRecordModelStub;
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
		$model = new ActiveRecordModelStub;
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
		$model = new ActiveRecordModelStub;
		$instance = $model->newInstance(array('name' => 'allan'));
		$this->assertInstanceOf('ActiveRecordModelStub', $instance);
		$this->assertEquals('allan', $instance->name);
	}


	public function testCreateMethodSavesNewModel()
	{
		$_SERVER['__active_record.saved'] = false;
		$model = ActiveRecordModelSaveStub::create(array('name' => 'allan'));
		$this->assertTrue($_SERVER['__active_record.saved']);
		$this->assertEquals('allan', $model->name);
	}


	public function testFindMethodCallsQueryBuilderCorrectly()
	{
		$result = ActiveRecordModelFindStub::find(1);
		$this->assertEquals('foo', $result);
	}

	/**
	 * @expectedException Fly\Database\ActiveRecord\ModelNotFoundException
	 */
	public function testFindOrFailMethodThrowsModelNotFoundException()
	{
		$result = ActiveRecordModelFindNotFoundStub::findOrFail(1);
	}

	public function testFindMethodWithArrayCallsQueryBuilderCorrectly()
	{
		$result = ActiveRecordModelFindManyStub::find(array(1, 2));
		$this->assertEquals('foo', $result);
	}


	public function testDestroyMethodCallsQueryBuilderCorrectly()
	{
		$result = ActiveRecordModelDestroyStub::destroy(1, 2, 3);
	}


	public function testWithMethodCallsQueryBuilderCorrectly()
	{
		$result = ActiveRecordModelWithStub::with('foo', 'bar');
		$this->assertEquals('foo', $result);
	}


	public function testWithMethodCallsQueryBuilderCorrectlyWithArray()
	{
		$result = ActiveRecordModelWithStub::with(array('foo', 'bar'));
		$this->assertEquals('foo', $result);
	}


	public function testUpdateProcess()
	{
		$model = $this->getMock('ActiveRecordModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Fly\Database\ActiveRecord\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('name' => 'allan'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('activerecord.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('activerecord.updating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('activerecord.updated: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('activerecord.saved: '.get_class($model), $model)->andReturn(true);

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
		$model = $this->getMock('ActiveRecordModelStub', array('newQuery'));
		$query = m::mock('Fly\Database\ActiveRecord\Builder');
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
		$model = $this->getMock('ActiveRecordModelStub', array('newQuery'));
		$query = m::mock('Fly\Database\ActiveRecord\Builder');
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('activerecord.saving: '.get_class($model), $model)->andReturn(false);
		$model->exists = true;

		$this->assertFalse($model->save());
	}


	public function testUpdateIsCancelledIfUpdatingEventReturnsFalse()
	{
		$model = $this->getMock('ActiveRecordModelStub', array('newQuery'));
		$query = m::mock('Fly\Database\ActiveRecord\Builder');
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('activerecord.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('activerecord.updating: '.get_class($model), $model)->andReturn(false);
		$model->exists = true;
		$model->foo = 'bar';

		$this->assertFalse($model->save());
	}


	public function testUpdateProcessWithoutTimestamps()
	{
		$model = $this->getMock('ActiveRecordModelStub', array('newQuery', 'updateTimestamps', 'fireModelEvent'));
		$model->timestamps = false;
		$query = m::mock('Fly\Database\ActiveRecord\Builder');
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
		$model = $this->getMock('ActiveRecordModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Fly\Database\ActiveRecord\Builder');
		$query->shouldReceive('where')->once()->with('id', '=', 1);
		$query->shouldReceive('update')->once()->with(array('id' => 2, 'foo' => 'bar'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('activerecord.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('activerecord.updating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('activerecord.updated: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('activerecord.saved: '.get_class($model), $model)->andReturn(true);

		$model->id = 1;
		$model->syncOriginal();
		$model->id = 2;
		$model->foo = 'bar';
		$model->exists = true;

		$this->assertTrue($model->save());
	}


	public function testTimestampsAreReturnedAsObjects()
	{
		$model = $this->getMock('ActiveRecordDateModelStub', array('getDateFormat'));
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
		$model = $this->getMock('ActiveRecordDateModelStub', array('getDateFormat'));
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
		$model = new ActiveRecordDateModelStub;
		Fly\Database\ActiveRecord\Model::setConnectionResolver($resolver = m::mock('Fly\Database\ConnectionResolverInterface'));
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
		$model = new ActiveRecordDateModelStub;
		Fly\Database\ActiveRecord\Model::setConnectionResolver($resolver = m::mock('Fly\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn($mockConnection = m::mock('StdClass'));
		$mockConnection->shouldReceive('getQueryGrammar')->andReturn($mockConnection);
		$mockConnection->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
		$instance = $model->newInstance($timestamps);

		$instance->created_at = null;
		$this->assertNull($instance->created_at);
	}


	public function testTimestampsAreCreatedFromStringsAndIntegers()
	{
		$model = new ActiveRecordDateModelStub;
		$model->created_at = '2013-05-22 00:00:00';
		$this->assertInstanceOf('Carbon\Carbon', $model->created_at);

		$model = new ActiveRecordDateModelStub;
		$model->created_at = time();
		$this->assertInstanceOf('Carbon\Carbon', $model->created_at);

		$model = new ActiveRecordDateModelStub;
		$model->created_at = '2012-01-01';
		$this->assertInstanceOf('Carbon\Carbon', $model->created_at);
	}


	public function testInsertProcess()
	{
		$model = $this->getMock('ActiveRecordModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Fly\Database\ActiveRecord\Builder');
		$query->shouldReceive('insertGetId')->once()->with(array('name' => 'allan'), 'id')->andReturn(1);
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');

		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('activerecord.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('activerecord.creating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('activerecord.created: '.get_class($model), $model);
		$events->shouldReceive('fire')->once()->with('activerecord.saved: '.get_class($model), $model);

		$model->name = 'allan';
		$model->exists = false;
		$this->assertTrue($model->save());
		$this->assertEquals(1, $model->id);
		$this->assertTrue($model->exists);

		$model = $this->getMock('ActiveRecordModelStub', array('newQuery', 'updateTimestamps'));
		$query = m::mock('Fly\Database\ActiveRecord\Builder');
		$query->shouldReceive('insert')->once()->with(array('name' => 'allan'));
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->expects($this->once())->method('updateTimestamps');
		$model->setIncrementing(false);

		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('activerecord.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('activerecord.creating: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('fire')->once()->with('activerecord.created: '.get_class($model), $model);
		$events->shouldReceive('fire')->once()->with('activerecord.saved: '.get_class($model), $model);

		$model->name = 'allan';
		$model->exists = false;
		$this->assertTrue($model->save());
		$this->assertNull($model->id);
		$this->assertTrue($model->exists);
	}


	public function testInsertIsCancelledIfCreatingEventReturnsFalse()
	{
		$model = $this->getMock('ActiveRecordModelStub', array('newQuery'));
		$query = m::mock('Fly\Database\ActiveRecord\Builder');
		$model->expects($this->once())->method('newQuery')->will($this->returnValue($query));
		$model->setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('until')->once()->with('activerecord.saving: '.get_class($model), $model)->andReturn(true);
		$events->shouldReceive('until')->once()->with('activerecord.creating: '.get_class($model), $model)->andReturn(false);

		$this->assertFalse($model->save());
		$this->assertFalse($model->exists);
	}


	public function testDeleteProperlyDeletesModel()
	{
		$model = $this->getMock('Fly\Database\ActiveRecord\Model', array('newQuery', 'updateTimestamps', 'touchOwners'));
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
		$model = $this->getMock('Fly\Database\ActiveRecord\Model', array('newQuery', 'updateTimestamps', 'touchOwners'));
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
		$model = $this->getMock('Fly\Database\ActiveRecord\Model', array('save'));
		$model->setSoftDeleting(true);
		$model->expects($this->once())->method('save');
		$model->restore();

		$this->assertNull($model->deleted_at);
	}


	public function testNewQueryReturnsActiveRecordQueryBuilder()
	{
		$conn = m::mock('Fly\Database\Connection');
		$grammar = m::mock('Fly\Database\Query\Grammars\Grammar');
		$processor = m::mock('Fly\Database\Query\Processors\Processor');
		$conn->shouldReceive('getQueryGrammar')->once()->andReturn($grammar);
		$conn->shouldReceive('getPostProcessor')->once()->andReturn($processor);
		ActiveRecordModelStub::setConnectionResolver($resolver = m::mock('Fly\Database\ConnectionResolverInterface'));
		$resolver->shouldReceive('connection')->andReturn($conn);
		$model = new ActiveRecordModelStub;
		$builder = $model->newQuery();
		$this->assertInstanceOf('Fly\Database\ActiveRecord\Builder', $builder);
	}


	public function testGetAndSetTableOperations()
	{
		$model = new ActiveRecordModelStub;
		$this->assertEquals('stub', $model->getTable());
		$model->setTable('foo');
		$this->assertEquals('foo', $model->getTable());
	}


	public function testGetKeyReturnsValueOfPrimaryKey()
	{
		$model = new ActiveRecordModelStub;
		$model->id = 1;
		$this->assertEquals(1, $model->getKey());
		$this->assertEquals('id', $model->getKeyName());
	}


	public function testConnectionManagement()
	{
		ActiveRecordModelStub::setConnectionResolver($resolver = m::mock('Fly\Database\ConnectionResolverInterface'));
		$model = new ActiveRecordModelStub;
		$model->setConnection('foo');
		$resolver->shouldReceive('connection')->once()->with('foo')->andReturn('bar');

		$this->assertEquals('bar', $model->getConnection());
	}


	public function testToArray()
	{
		$model = new ActiveRecordModelStub;
		$model->name = 'foo';
		$model->age = null;
		$model->password = 'password1';
		$model->setHidden(array('password'));
		$model->setRelation('names', new Fly\Database\ActiveRecord\Collection(array(
			new ActiveRecordModelStub(array('bar' => 'baz')), new ActiveRecordModelStub(array('bam' => 'boom'))
		)));
		$model->setRelation('partner', new ActiveRecordModelStub(array('name' => 'abby')));
		$model->setRelation('group', null);
		$model->setRelation('multi', new Fly\Database\ActiveRecord\Collection);
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
		$model = new ActiveRecordModelStub;
		$model->setVisible(array('name'));
		$model->name = 'Allan';
		$model->age = 26;
		$array = $model->toArray();

		$this->assertEquals(array('name' => 'Allan'), $array);
	}


	public function testHiddenCanAlsoExcludeRelationships()
	{
		$model = new ActiveRecordModelStub;
		$model->name = 'Allan';
		$model->setRelation('foo', array('bar'));
		$model->setHidden(array('foo', 'list_items', 'password'));
		$array = $model->toArray();

		$this->assertEquals(array('name' => 'Allan'), $array);
	}


	public function testToArraySnakeAttributes()
	{
		$model = new ActiveRecordModelStub;
		$model->setRelation('namesList', new Fly\Database\ActiveRecord\Collection(array(
			new ActiveRecordModelStub(array('bar' => 'baz')), new ActiveRecordModelStub(array('bam' => 'boom'))
		)));
		$array = $model->toArray();

		$this->assertEquals('baz', $array['names_list'][0]['bar']);
		$this->assertEquals('boom', $array['names_list'][1]['bam']);

		$model = new ActiveRecordModelCamelStub;
		$model->setRelation('namesList', new Fly\Database\ActiveRecord\Collection(array(
			new ActiveRecordModelStub(array('bar' => 'baz')), new ActiveRecordModelStub(array('bam' => 'boom'))
		)));
		$array = $model->toArray();

		$this->assertEquals('baz', $array['namesList'][0]['bar']);
		$this->assertEquals('boom', $array['namesList'][1]['bam']);
	}


	public function testToArrayUsesMutators()
	{
		$model = new ActiveRecordModelStub;
		$model->list_items = array(1, 2, 3);
		$array = $model->toArray();

		$this->assertEquals(array(1, 2, 3), $array['list_items']);
	}


	public function testFillable()
	{
		$model = new ActiveRecordModelStub;
		$model->fillable(array('name', 'age'));
		$model->fill(array('name' => 'foo', 'age' => 'bar'));
		$this->assertEquals('foo', $model->name);
		$this->assertEquals('bar', $model->age);
	}


	public function testUnguardAllowsAnythingToBeSet()
	{
		$model = new ActiveRecordModelStub;
		ActiveRecordModelStub::unguard();
		$model->guard(array('*'));
		$model->fill(array('name' => 'foo', 'age' => 'bar'));
		$this->assertEquals('foo', $model->name);
		$this->assertEquals('bar', $model->age);
		ActiveRecordModelStub::setUnguardState(false);
	}


	public function testUnderscorePropertiesAreNotFilled()
	{
		$model = new ActiveRecordModelStub;
		$model->fill(array('_method' => 'PUT'));
		$this->assertEquals(array(), $model->getAttributes());
	}


	public function testGuarded()
	{
		$model = new ActiveRecordModelStub;
		$model->guard(array('name', 'age'));
		$model->fill(array('name' => 'foo', 'age' => 'bar', 'foo' => 'bar'));
		$this->assertFalse(isset($model->name));
		$this->assertFalse(isset($model->age));
		$this->assertEquals('bar', $model->foo);
	}


	public function testFillableOverridesGuarded()
	{
		$model = new ActiveRecordModelStub;
		$model->guard(array('name', 'age'));
		$model->fillable(array('age', 'foo'));
		$model->fill(array('name' => 'foo', 'age' => 'bar', 'foo' => 'bar'));
		$this->assertFalse(isset($model->name));
		$this->assertEquals('bar', $model->age);
		$this->assertEquals('bar', $model->foo);
	}


	/**
	 * @expectedException Fly\Database\ActiveRecord\MassAssignmentException
	 */
	public function testGlobalGuarded()
	{
		$model = new ActiveRecordModelStub;
		$model->guard(array('*'));
		$model->fill(array('name' => 'foo', 'age' => 'bar', 'votes' => 'baz'));
	}


	public function testHasOneCreatesProperRelation()
	{
		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasOne('ActiveRecordModelSaveStub');
		$this->assertEquals('save_stub.active_record_model_stub_id', $relation->getForeignKey());

		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasOne('ActiveRecordModelSaveStub', 'foo');
		$this->assertEquals('save_stub.foo', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof ActiveRecordModelSaveStub);
	}


	public function testMorphOneCreatesProperRelation()
	{
		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->morphOne('ActiveRecordModelSaveStub', 'morph');
		$this->assertEquals('save_stub.morph_id', $relation->getForeignKey());
		$this->assertEquals('save_stub.morph_type', $relation->getMorphType());
		$this->assertEquals('ActiveRecordModelStub', $relation->getMorphClass());
	}


	public function testHasManyCreatesProperRelation()
	{
		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasMany('ActiveRecordModelSaveStub');
		$this->assertEquals('save_stub.active_record_model_stub_id', $relation->getForeignKey());

		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->hasMany('ActiveRecordModelSaveStub', 'foo');
		$this->assertEquals('save_stub.foo', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof ActiveRecordModelSaveStub);
	}


	public function testMorphManyCreatesProperRelation()
	{
		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->morphMany('ActiveRecordModelSaveStub', 'morph');
		$this->assertEquals('save_stub.morph_id', $relation->getForeignKey());
		$this->assertEquals('save_stub.morph_type', $relation->getMorphType());
		$this->assertEquals('ActiveRecordModelStub', $relation->getMorphClass());
	}


	public function testBelongsToCreatesProperRelation()
	{
		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToStub();
		$this->assertEquals('belongs_to_stub_id', $relation->getForeignKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof ActiveRecordModelSaveStub);

		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToExplicitKeyStub();
		$this->assertEquals('foo', $relation->getForeignKey());
	}


	public function testMorphToCreatesProperRelation()
	{
		$model = m::mock('Fly\Database\ActiveRecord\Model[belongsTo]');
		$model->foo_type = 'FooClass';
		$model->shouldReceive('belongsTo')->with('FooClass', 'foo_id');
		$relation = $model->morphTo('foo');

		$model = m::mock('ActiveRecordModelStub[belongsTo]');
		$model->morph_to_stub_type = 'FooClass';
		$model->shouldReceive('belongsTo')->with('FooClass', 'morph_to_stub_id');
		$relation = $model->morphToStub();
	}


	public function testBelongsToManyCreatesProperRelation()
	{
		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToMany('ActiveRecordModelSaveStub');
		$this->assertEquals('active_record_model_save_stub_active_record_model_stub.active_record_model_stub_id', $relation->getForeignKey());
		$this->assertEquals('active_record_model_save_stub_active_record_model_stub.active_record_model_save_stub_id', $relation->getOtherKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof ActiveRecordModelSaveStub);

		$model = new ActiveRecordModelStub;
		$this->addMockConnection($model);
		$relation = $model->belongsToMany('ActiveRecordModelSaveStub', 'table', 'foreign', 'other');
		$this->assertEquals('table.foreign', $relation->getForeignKey());
		$this->assertEquals('table.other', $relation->getOtherKey());
		$this->assertTrue($relation->getParent() === $model);
		$this->assertTrue($relation->getQuery()->getModel() instanceof ActiveRecordModelSaveStub);
	}


	public function testModelsAssumeTheirName()
	{
		$model = new ActiveRecordModelWithoutTableStub;
		$this->assertEquals('active_record_model_without_table_stubs', $model->getTable());

		require_once __DIR__.'/stubs/ActiveRecordModelNamespacedStub.php';
		$namespacedModel = new Foo\Bar\ActiveRecordModelNamespacedStub;
		$this->assertEquals('active_record_model_namespaced_stubs', $namespacedModel->getTable());
	}


	public function testTheMutatorCacheIsPopulated()
	{
		$class = new ActiveRecordModelStub;

		$this->assertEquals(array('list_items', 'password', 'appendable'), $class->getMutatedAttributes());
	}


	public function testCloneModelMakesAFreshCopyOfTheModel()
	{
		$class = new ActiveRecordModelStub;
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
		ActiveRecordModelStub::setEventDispatcher($events = m::mock('Fly\Events\Dispatcher'));
		$events->shouldReceive('listen')->once()->with('activerecord.creating: ActiveRecordModelStub', 'ActiveRecordTestObserverStub@creating');
		$events->shouldReceive('listen')->once()->with('activerecord.saved: ActiveRecordModelStub', 'ActiveRecordTestObserverStub@saved');
		$events->shouldReceive('forget');
		ActiveRecordModelStub::observe(new ActiveRecordTestObserverStub);
		ActiveRecordModelStub::flushEventListeners();
	}


	/**
	 * @expectedException LogicException
	 */
	public function testGetModelAttributeMethodThrowsExceptionIfNotRelation()
	{
		$model = new ActiveRecordModelStub;
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

class ActiveRecordTestObserverStub {
	public function creating() {}
	public function saved() {}
}
class ActiveRecordModelStub extends Fly\Database\ActiveRecord\Model {
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
		return $this->belongsTo('ActiveRecordModelSaveStub');
	}
	public function morphToStub()
	{
		return $this->morphTo();
	}
	public function belongsToExplicitKeyStub()
	{
		return $this->belongsTo('ActiveRecordModelSaveStub', 'foo');
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

class ActiveRecordModelCamelStub extends ActiveRecordModelStub {
	public static $snakeAttributes = false;
}

class ActiveRecordDateModelStub extends ActiveRecordModelStub {
	public function getDates()
	{
		return array('created_at', 'updated_at');
	}
}

class ActiveRecordModelSaveStub extends Fly\Database\ActiveRecord\Model {
	protected $table = 'save_stub';
	protected $guarded = array();
	public function save(array $options = array()) { $_SERVER['__active_record.saved'] = true; }
	public function setIncrementing($value)
	{
		$this->incrementing = $value;
	}
}

class ActiveRecordModelFindStub extends Fly\Database\ActiveRecord\Model {
	public function newQuery($excludeDeleted = true)
	{
		$mock = m::mock('Fly\Database\ActiveRecord\Builder');
		$mock->shouldReceive('find')->once()->with(1, array('*'))->andReturn('foo');
		return $mock;
	}
}

class ActiveRecordModelFindNotFoundStub extends Fly\Database\ActiveRecord\Model {
	public function newQuery($excludeDeleted = true)
	{
		$mock = m::mock('Fly\Database\ActiveRecord\Builder');
		$mock->shouldReceive('find')->once()->with(1, array('*'))->andReturn(null);
		return $mock;
	}
}

class ActiveRecordModelDestroyStub extends Fly\Database\ActiveRecord\Model {
	public function newQuery($excludeDeleted = true)
	{
		$mock = m::mock('Fly\Database\ActiveRecord\Builder');
		$mock->shouldReceive('whereIn')->once()->with('id', array(1, 2, 3))->andReturn($mock);
		$mock->shouldReceive('get')->once()->andReturn(array($model = m::mock('StdClass')));
		$model->shouldReceive('delete')->once();
		return $mock;
	}
}

class ActiveRecordModelFindManyStub extends Fly\Database\ActiveRecord\Model {
	public function newQuery($excludeDeleted = true)
	{
		$mock = m::mock('Fly\Database\ActiveRecord\Builder');
		$mock->shouldReceive('find')->once()->with(array(1, 2), array('*'))->andReturn('foo');
		return $mock;
	}
}

class ActiveRecordModelWithStub extends Fly\Database\ActiveRecord\Model {
	public function newQuery($excludeDeleted = true)
	{
		$mock = m::mock('Fly\Database\ActiveRecord\Builder');
		$mock->shouldReceive('with')->once()->with(array('foo', 'bar'))->andReturn('foo');
		return $mock;
	}
}

class ActiveRecordModelWithoutTableStub extends Fly\Database\ActiveRecord\Model {}
