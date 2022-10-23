<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

use Threema\MsgApi\Commands\Results\Result;

interface CommandInterface {
	/**
	 * @return string
	 */
	function getPath();

	/**
	 * @return array
	 */
	function getParams();

	/**
	 * @param int $httpCode
	 * @param object $res
	 * @return Result
	 */
	function parseResult($httpCode, $res);
}
