<?php namespace Fly\Database\Orm;

class ModelNotFoundException extends \RuntimeException {
	
	/**
	 * Name of the affected Orm model.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Set the affected Orm model.
	 *
	 * @param  string   $model
	 * @return ModelNotFoundException
	 */
	public function setModel($model)
	{
		$this->model = $model;

		$this->message = "No query results for model [{$model}].";

		return $this;
	}

	/**
	 * Get the affected Orm model.
	 *
	 * @return string
	 */
	public function getModel()
	{
		return $this->model;
	}

}