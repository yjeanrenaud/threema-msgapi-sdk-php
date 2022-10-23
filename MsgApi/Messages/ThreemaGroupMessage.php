<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;
use Threema\MsgApi\Types\GroupId;

/**
 * Abstract base class of a group messages that can be sent with end-to-end encryption via Threema.
 */
abstract class ThreemaGroupMessage extends ThreemaMessage {
	/**
	 * @var GroupId
	 */
	private $groupId;

	public function __construct(GroupId $groupId) {
		$this->groupId = $groupId;
	}

	/**
	 * @return GroupId
	 */
	public function getGroupId() {
		return $this->groupId;
	}

}
