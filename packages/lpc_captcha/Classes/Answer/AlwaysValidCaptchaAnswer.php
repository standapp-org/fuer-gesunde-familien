<?php
namespace LPC\Captcha\Answer;

class AlwaysValidCaptchaAnswer extends CaptchaAnswer
{
	public function __construct() {
		parent::__construct('', '');
	}
}
