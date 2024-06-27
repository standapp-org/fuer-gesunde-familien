<?php
namespace LPC\LpcBase\Update;

class UpdateException extends \Exception
{
	protected $failUpdate;

	public function __construct($message,$failUpdate = false) {
		parent::__construct($message);
		$this->failUpdate = $failUpdate;
	}

	public function failsUpdate() {
		return $this->failUpdate;
	}
}
