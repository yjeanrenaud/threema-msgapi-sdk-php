<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

use Threema\MsgApi\Types\GroupId;

class GroupTextMessage extends ThreemaGroupMessage {
	const TYPE_CODE = 0x41;

	/**
	 * @var string
	 */
	private $text;

	/**
	 * @param GroupId $groupId
	 * @param string $text
	 */
	function __construct(GroupId $groupId, $text) {
		parent::__construct($groupId);
		$this->text = $text;
	}

	/**
	 * @return string text
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * @return string
	 */
	function __toString() {
		return 'group text message';
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
