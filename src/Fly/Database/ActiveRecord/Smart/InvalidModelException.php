<?php namespace Fly\Database\ActiveRecord\Smart;

/**
 * Used when validation fails. Contains the invalid model for easy analysis.
 * Class InvalidModelException
 */
class InvalidModelException extends \RuntimeException {

	/**
	 * The invalid model.
	 * @var \Fly\Database\ActiveRecord\Smart\SmartModel
	 */
	protected $model;

	/**
	 * The message bag instance containing validation error messages
	 * @var \Fly\Support\MessageBag
	 */
	protected $errors;

	/**
	 * Receives the invalid model and sets the {@link model} and {@link errors} properties.
	 * @param SmartModel $model The troublesome model.
	 */
	public function __construct(Ardent $model) {
		$this->model  = $model;
		$this->errors = $model->errors();
	}

	/**
	 * Returns the model with invalid attributes.
	 * @return SmartModel
	 */
	public function getModel() {
		return $this->model;
	}

	/**
	 * Returns directly the message bag instance with the model's errors.
	 * @return \Fly\Support\MessageBag
	 */
	public function getErrors() {
		return $this->errors;
	}
}