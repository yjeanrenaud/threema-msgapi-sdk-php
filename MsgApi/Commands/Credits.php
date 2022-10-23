<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

use Threema\MsgApi\Commands\Results\CreditsResult;

class Credits implements CommandInterface {
	/**
	 * @return array
	 */
	function getParams() {
		return [];
	}

	function getPath() {
		return 'credits';
	}

	/**
	 * @param int $httpCode
	 * @param object $res
	 * @return CreditsResult
	 */
	function parseResult($httpCode, $res){
		return new CreditsResult($httpCode, $res);
	}
}
