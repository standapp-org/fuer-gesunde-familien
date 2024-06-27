<?php
namespace LPC\LpcBase\Mail;

class MailMessage extends FluidEmail
{
	private $stringBody = [];

	public function setBodyTemplate($name) {
		$this->setTemplate($name);
		$this->stringBody = [];
	}

	public function setBodyString($body, $mimeType = 'text/plain') {
		$format = $mimeType === 'text/html' ? self::FORMAT_HTML : self::FORMAT_PLAIN;
		$this->stringBody[$format] = $body;
		$this->format = array_keys($this->stringBody);
	}

	public function setSubject($subject) {
		$this->subject($subject);
	}

	public function setFrom($email, $name = null) {
		$this->from(...$this->convertEmailAddresses($email, $name));
	}

	public function setTo($email, $name = null) {
		$this->to(...$this->convertEmailAddresses($email, $name));
	}

	public function setReplyTo($email, $name = null) {
		$this->replyTo(...$this->convertEmailAddresses($email, $name));
	}

	private function convertEmailAddresses($email, $name) {
		$addresses = [];
		if(is_array($email)) {
			foreach($email as $k => $v) {
				if(is_numeric($k)) {
					$addresses[] = new \Symfony\Component\Mime\Address($v);
				} else {
					$addresses[] = new \Symfony\Component\Mime\Address($k, $v);
				}
			}
		} else if($email) {
			$addresses[] = new \Symfony\Component\Mime\Address($email, $name ?: '');
		}
		return $addresses;
	}

	protected function renderContent(string $format): string {
		return $this->stringBody[$format] ?? parent::renderContent($format);
	}
}
