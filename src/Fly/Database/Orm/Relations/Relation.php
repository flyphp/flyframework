<?php namespace Fly\Database\Orm\Relations;

use Closure;
use Fly\Database\Orm\Model;
use Fly\Database\Orm\Builder;
use Fly\Database\Query\Expression;
use Fly\Database\Orm\Collection;

abstract class Relation {

	/**
	 * The FlyORM query builder instance.
	 *
	 * @var \Fly\Database\Orm\Builder
	 */
	protected $query;

	/**
	 * The parent model instance.
	 *
	 * @var \Fly\Database\Orm\Model
	 */
	protected $parent;

	/**
	 * The related model instance.
	 *
	 * @var \Fly\Database\Orm\Model
	 */
	protected $related;

	/**
	 * Indicates if the relation is adding constraints.
	 *
	 * @var bool
	 */
	protected static $constraints = true;

	/**
	 * Create a new relation instance.
	 *
	 * @param  \Fly\Database\Orm\Builder  $query
	 * @param  \Fly\Database\Orm\Model  $parent
	 * @return void
	 */
	public function __construct(Builder $query, Model $parent)
	{
		$this->query = $query;
		$this->parent = $parent;
		$this->related = $query->getModel();

		$this->addConstraints();
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	abstract public function addConstraints();

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	abstract public function addEagerConstraints(array $models);

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 * @return void
	 */
	abstract public function initRelation(array $models, $relation);

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $models
	 * @param  \Fly\Database\Orm\Collection  $results
	 * @param  string  $relation
	 * @return array
	 */
	abstract public function match(array $models, Collection $results, $relation);

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	abstract public function getResults();

	/**
	 * Touch all of the related models for the relationship.
	 *
	 * @return void
	 */
	public function touch()
	{
		$column = $this->getRelated()->getUpdatedAtColumn();

		$this->rawUpdate(array($column => $this->getRelated()->freshTimestampString()));
	}

	/**
	 * Restore all of the soft deleted related models.
	 *
	 * @return int
	 */
	public function restore()
	{
		return $this->query->withTrashed()->restore();
	}

	/**
	 * Run a raw update against the base query.
	 *
	 * @param  array  $attributes
	 * @return int
	 */
	public function rawUpdate(array $attributes = array())
	{
		return $this->query->update($attributes);
	}

	/**
	 * Add the constraints for a relationship count query.
	 *
	 * @param  \Fly\Database\Orm\Builder  $query
	 * @param  \Fly\Database\Orm\Builder  $parent
	 * @return \Fly\Database\Orm\Builder
	 */
	public function getRelationCountQuery(Builder $query, Builder $parent)
	{
		$query->select(new Expression('count(*)'));

		$key = $this->wrap($this->getQualifiedParentKeyName());

		return $query->where($this->getHasCompareKey(), '=', new Expression($key));
	}

	/**
	 * Run a callback with constrains disabled on the relation.
	 *
	 * @param  \Closure  $callback
	 * @return mixed
	 */
	public static function noConstraints(Closure $callback)
	{
		static::$constraints = false;

		// When resetting the relation where clause, we want to shift the first element
		// off of the bindings, leaving only the constraints that the developers put
		// as "extra" on the relationships, and not original relation constraints.
		$results = call_user_func($callback);

		static::$constraints = true;

		return $results;
	}

	/**
	 * Get all of the primary keys for an array of models.
	 *
	 * @param  array   $models
	 * @param  string  $key
	 * @return array
	 */
	protected function getKeys(array $models, $key = null)
	{
		return array_values(array_map(function($value) use ($key)
		{
			return $key ? $value->getAttribute($key) : $value->getKey();

		}, $models));
	}

	/**
	 * Get the underlying query for the relation.
	 *
	 * @return \Fly\Database\Orm\Builder
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * Get the base query builder driving the FlyORM builder.
	 *
	 * @return \Fly\Database\Query\Builder
	 */
	public function getBaseQuery()
	{
		return $this->query->getQuery();
	}

	/**
	 * Get the parent model of the relation.
	 *
	 * @return \Fly\Database\Orm\Model
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Get the fully qualified parent key naem.
	 *
	 * @return string
	 */
	protected function getQualifiedParentKeyName()
	{
		return $this->parent->getQualifiedKeyName();
	}

	/**
	 * Get the related model of the relation.
	 *
	 * @return \Fly\Database\Orm\Model
	 */
	public function getRelated()
	{
		return $this->related;
	}

	/**
	 * Get the name of the "created at" column.
	 *
	 * @return string
	 */
	public function createdAt()
	{
		return $this->parent->getCreatedAtColumn();
	}

	/**
	 * Get the name of the "updated at" column.
	 *
	 * @return string
	 */
	public function updatedAt()
	{
		return $this->parent->getUpdatedAtColumn();
	}

	/**
	 * Get the name of the related model's "updated at" column.
	 *
	 * @return string
	 */
	public function relatedUpdatedAt()
	{
		return $this->related->getUpdatedAtColumn();
	}

	/**
	 * Wrap the given value with the parent query's grammar.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function wrap($value)
	{
		return $this->parent->getQuery()->getGrammar()->wrap($value);
	}

	/**
	 * Handle dynamic method calls to the relationship.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$result = call_user_func_array(array($this->query, $method), $parameters);

		if ($result === $this->query) return $this;

		return $result;
	}

}