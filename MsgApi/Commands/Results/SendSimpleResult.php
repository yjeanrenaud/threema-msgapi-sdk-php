<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands\Results;

class SendSimpleResult extends Result {
	/**
	 * @var string
	 */
	private $messageId;

	/**
	 * @param string $response
	 */
	protected function processResponse($response) {
		$this->messageId = (string)$response;
	}

	/**
	 * @return string
	 */
	public function getMessageId() {
		return $this->messageId;
	}

	/**
	 * @param int $httpCode
	 * @return string
	 */
	protected function getErrorMessageByErrorCode($httpCode) {
		switch($httpCode) {
			case 400:
				return 'The recipient identity is invalid or the account is not set up for simple mode';
			case 401:
				return 'API identity or secret incorrect';
			case 402:
				return 'No credits remain';
			case 404:
				return 'Phone or email could not be found';
			case 413:
				return 'Message is too long';
			case 500:
				return 'A temporary internal server error has occurred';
			default:
				return 'Unknown error';
		}
	}
}
