<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

/**
 * Abstract base class of messages that can be sent with end-to-end encryption via Threema.
 */
abstract class ThreemaMessage {

	/**
	 * Get the message type code of this message.
	 *
	 * @return int message type code
	 */
	abstract public function getTypeCode();
}
