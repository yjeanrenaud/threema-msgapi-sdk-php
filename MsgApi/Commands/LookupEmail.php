<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

use Threema\MsgApi\Commands\Results\LookupIdResult;
use Threema\MsgApi\Tools\CryptTool;

class LookupEmail implements CommandInterface {
	/**
	 * @var string
	 */
	private $emailAddress;

	/**
	 * @param string $emailAddress
	 */
	function __construct($emailAddress) {
		$this->emailAddress = $emailAddress;
	}

	/**
	 * @return string
	 */
	public function getEmailAddress() {
		return $this->emailAddress;
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
		return 'lookup/email_hash/'.urlencode(CryptTool::getInstance()->hashEmail($this->emailAddress));
	}

	/**
	 * @param int $httpCode
	 * @param object $res
	 * @return LookupIdResult
	 */
	function parseResult($httpCode, $res){
		return new LookupIdResult($httpCode, $res);
	}
}
