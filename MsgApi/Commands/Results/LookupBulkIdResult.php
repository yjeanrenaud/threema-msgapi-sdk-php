<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands\Results;

use LookupBulkIdResultItem;

class LookupBulkIdResult extends Result {
	/**
	 * @var LookupBulkIdResultItem[]
	 */
	private $result = [];

	/**
	 * @param string $response
	 */
	protected function processResponse($response) {
		$this->result = json_decode($response, false);
	}

	/**
	 * @return LookupBulkIdResultItem[]
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * @param int $httpCode
	 * @return string
	 */
	protected function getErrorMessageByErrorCode($httpCode) {
		switch($httpCode) {
			case 400:
				return 'Hash length is wrong';
			case 401:
				return 'API identity or secret incorrect';
			case 404:
				return 'No matching ID found';
			case 413:
				return 'Roo many hashes have been specified in the request';
			case 500:
				return 'A temporary internal server error has occurred';
			default:
				return 'Unknown error';
		}
	}


}
