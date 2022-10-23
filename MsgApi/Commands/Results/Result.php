<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands\Results;

abstract class Result {
	/**
	 * @var int
	 */
	private $httpCode;

	/**
	 * @var string
	 */
	private $response;

	/**
	 * @param int $httpCode
	 * @param $response
	 */
	function __construct($httpCode, $response) {
		$this->httpCode = $httpCode;
		$this->processResponse($response);
		$this->response = $response;
	}

	public function isSuccess() {
		return $this->httpCode == 200;
	}

	/**
	 * @return null|int
	 */
	final public function getErrorCode() {
		if(false === $this->isSuccess()) {
			return $this->httpCode;
		}
		return null;
	}

	/**
	 * @return string
	 */
	final public function getErrorMessage() {
		return $this->getErrorMessageByErrorCode($this->getErrorCode());
	}

	/**
	 * @return string
	 */
	final public function getRawResponse() {
		return $this->response;
	}

	/**
	 * @param int $httpCode
	 * @return string
	 */
	abstract protected function getErrorMessageByErrorCode($httpCode);

	/**
	 * @param string $response
	 */
	abstract protected function processResponse($response);
}
