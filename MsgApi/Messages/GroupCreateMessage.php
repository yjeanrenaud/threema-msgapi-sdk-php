<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

class GroupCreateMessage extends ThreemaGroupMessage {
	const TYPE_CODE = 0x4a;

	/**
	 * @return string
	 */
	function __toString() {
		return 'group create message';
	}

	/**
	 * Get the message type code of this message.
	 *
	 * @return int message type code
	 */
	public function getTypeCode() {
		return self::TYPE_CODE;
	}
}
