<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

class GroupLeaveMessage extends ThreemaGroupMessage {
	const TYPE_CODE = 0x4c;

	/**
	 * @return string
	 */
	function __toString() {
		return 'group leave message';
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
