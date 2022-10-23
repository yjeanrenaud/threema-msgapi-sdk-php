<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

use Threema\MsgApi\Commands\Results\FetchPublicKeyResult;

class FetchPublicKey implements CommandInterface {
	/**
	 * @var string
	 */
	private $threemaId;

	/**
	 * @param string $threemaId
	 */
	function __construct($threemaId) {
		$this->threemaId = $threemaId;
	}


	/**
	 * @return array
	 */
	function getParams() {
		return [];
	}

	/**
	 * @return string
	 */
	function getPath() {
		return 'pubkeys/'.urlencode($this->threemaId);
	}

	/**
	 * @param int $httpCode
	 * @param object $res
	 * @return FetchPublicKeyResult
	 */
	function parseResult($httpCode, $res){
		return new FetchPublicKeyResult($httpCode, $res);
	}
}
