<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Types;

use Threema\MsgApi\Core\Exception;

class GroupId  {
	/**
	 * @var string
	 */
	private $groupCreator;

	/**
	 * @var string
	 */
	private $groupId;

	/**
	 * @param string $groupCreator
	 * @param string $groupId
	 */
	public function __construct($groupCreator, $groupId) {
		$this->groupCreator = $groupCreator;
		$this->groupId = $groupId;
	}

	/**
	 * @return string
	 */
	public function getGroupCreator() {
		return $this->groupCreator;
	}

	/**
	 * @param string $creator
	 * @return $this
	 * @throws Exception
	 */
	public function setGroupCreator($creator) {
		if (null !== $this->groupCreator) {
			throw new Exception('Creator already set');
		}
		$this->groupCreator = $creator;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getGroupId() {
		return $this->groupId;
	}
}
