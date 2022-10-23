<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

interface JsonCommandInterface extends CommandInterface {
	/**
	 * @return array
	 */
	function getData();
}
