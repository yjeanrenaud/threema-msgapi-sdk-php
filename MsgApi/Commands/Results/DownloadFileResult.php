<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands\Results;

class DownloadFileResult extends Result {
	/**
	 * @var string
	 */
	private $data;

	/**
	 * @param string $data
	 */
	protected function processResponse($data) {
		$this->data = $data;
	}

	/**
	 * the generated blob id
	 *
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param int $httpCode
	 * @return string
	 */
	protected function getErrorMessageByErrorCode($httpCode) {
		switch($httpCode) {
			case 401:
				return 'API identity or secret incorrect';
			case 404:
				return 'Invalid blob id';
			case 500:
				return 'A temporary internal server error has occurred';
			default:
				return 'Unknown error';
		}
	}
}

