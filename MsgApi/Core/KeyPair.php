<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Core;

class KeyPair {
	public $privateKey;
	public $publicKey;

	function __construct($privateKey, $publicKey) {
		$this->privateKey = $privateKey;
		$this->publicKey = $publicKey;
	}
}
