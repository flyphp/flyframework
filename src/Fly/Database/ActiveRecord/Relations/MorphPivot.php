<?php namespace Fly\Database\ActiveRecord\Relations;

use Fly\Database\ActiveRecord\Builder;

class MorphPivot extends Pivot {

	/**
	 * Set the keys for a save update query.
	 *
	 * @param  \Fly\Database\ActiveRecord\Builder
	 * @return \Fly\Database\ActiveRecord\Builder
	 */
	protected function setKeysForSaveQuery(Builder $query)
	{
		$query->where($this->morphType, $this->getAttribute($this->morphType));

		return parent::setKeysForSaveQuery($query);
	}

	/**
	 * Delete the pivot model record from the database.
	 *
	 * @return int
	 */
	public function delete()
	{
		$query = $this->getDeleteQuery();

		$query->where($this->morphType, $this->getAttribute($this->morphType));

		return $query->delete();
	}

	/**
	 * Set the morph type for the pivot.
	 *
	 * @param  string  $morphType
	 * @return \Fly\Database\ActiveRecord\Relations\MorphPivot
	 */
	public function setMorphType($morphType)
	{
		$this->morphType = $morphType;

		return $this;
	}

}
