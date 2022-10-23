<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

use Threema\MsgApi\Commands\Results\CapabilityResult;

class Capability implements CommandInterface {
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

	function getPath() {
		return 'capabilities/'.urlencode($this->threemaId);
	}

	/**
	 * @param int $httpCode
	 * @param object $res
	 * @return CapabilityResult
	 */
	function parseResult($httpCode, $res){
		return new CapabilityResult($httpCode, $res);
	}
}
