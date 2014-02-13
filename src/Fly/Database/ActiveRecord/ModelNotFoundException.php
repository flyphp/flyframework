<?php namespace Fly\Database\ActiveRecord;

class ModelNotFoundException extends \RuntimeException {
	
	/**
	 * Name of the affected ActiveRecord model.
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Set the affected ActiveRecord model.
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
	 * Get the affected ActiveRecord model.
	 *
	 * @return string
	 */
	public function getModel()
	{
		return $this->model;
	}

}