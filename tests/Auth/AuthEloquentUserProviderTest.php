<?php

use Mockery as m;

class AuthOrmUserProviderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRetrieveByIDReturnsUser()
	{
		$provider = $this->getProviderMock();
		$mock = m::mock('stdClass');
		$mock->shouldReceive('newQuery')->once()->andReturn($mock);
		$mock->shouldReceive('find')->once()->with(1)->andReturn('bar');
		$provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
		$user = $provider->retrieveByID(1);

		$this->assertEquals('bar', $user);
	}


	public function testRetrieveByCredentialsReturnsUser()
	{
		$provider = $this->getProviderMock();
		$mock = m::mock('stdClass');
		$mock->shouldReceive('newQuery')->once()->andReturn($mock);
		$mock->shouldReceive('where')->once()->with('username', 'dayle');
		$mock->shouldReceive('first')->once()->andReturn('bar');
		$provider->expects($this->once())->method('createModel')->will($this->returnValue($mock));
		$user = $provider->retrieveByCredentials(array('username' => 'dayle', 'password' => 'foo'));

		$this->assertEquals('bar', $user);
	}


	public function testCredentialValidation()
	{
		$conn = m::mock('Fly\Database\Connection');
		$hasher = m::mock('Fly\Hashing\HasherInterface');
		$hasher->shouldReceive('check')->once()->with('plain', 'hash')->andReturn(true);
		$provider = new Fly\Auth\OrmUserProvider($hasher, 'foo');
		$user = m::mock('Fly\Auth\UserInterface');
		$user->shouldReceive('getAuthPassword')->once()->andReturn('hash');
		$result = $provider->validateCredentials($user, array('password' => 'plain'));

		$this->assertTrue($result);
	}


	public function testModelsCanBeCreated()
	{
		$conn = m::mock('Fly\Database\Connection');
		$hasher = m::mock('Fly\Hashing\HasherInterface');
		$provider = new Fly\Auth\OrmUserProvider($hasher, 'OrmProviderUserStub');
		$model = $provider->createModel();

		$this->assertInstanceOf('OrmProviderUserStub', $model);
	}


	protected function getProviderMock()
	{
		$hasher = m::mock('Fly\Hashing\HasherInterface');
		return $this->getMock('Fly\Auth\OrmUserProvider', array('createModel'), array($hasher, 'foo'));
	}

}

class OrmProviderUserStub {}