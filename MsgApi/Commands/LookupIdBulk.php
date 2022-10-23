<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

use Threema\MsgApi\Commands\Results\LookupBulkIdResult;
use Threema\MsgApi\Tools\CryptTool;

class LookupIdBulk implements JsonCommandInterface {
	/**
	 * @var array
	 */
	private $emailAddresses;
	/**
	 * @var array
	 */
	private $phoneNumbers;
	/**
	 * @var bool
	 */
	private $hashed;

	/**
	 * LookupIdBulk constructor.
	 * @param array $emailAddresses
	 * @param array $phoneNumbers
	 * @param bool $hashed
	 */
	function __construct(array $emailAddresses, array $phoneNumbers, $hashed = true) {
		$this->emailAddresses = $emailAddresses;
		$this->phoneNumbers = $phoneNumbers;
		$this->hashed = $hashed === true;
	}

	/**
	 * @return array
	 */
	function getData() {
		$emailHashes = $this->emailAddresses;
		$phoneHashes = $this->phoneNumbers;

		if(false === $this->hashed) {
			//hash all email and phone numbers
			$cryptoTool = CryptTool::getInstance();
			foreach($emailHashes as $index => $email) {
				$emailHashes[$index] = $cryptoTool->hashEmail($email);
			}
			foreach($phoneHashes as $index => $phone) {
				$phoneHashes[$index] = $cryptoTool->hashPhoneNo($phone);
			}
		}
		return [
			'phoneHashes' => $phoneHashes,
			'emailHashes' => $emailHashes
		];
	}

	function getParams() {
		return [];
	}


	/**
	 * @return string
	 */
	function getPath() {
		return 'lookup/bulk';
	}

	/**
	 * @param int $httpCode
	 * @param object $res
	 * @return LookupBulkIdResult
	 */
	function parseResult($httpCode, $res){
		return new LookupBulkIdResult($httpCode, $res);
	}
}
