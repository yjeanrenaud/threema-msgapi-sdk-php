<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

use Threema\MsgApi\Types\GroupId;

class GroupImageMessage extends ThreemaGroupMessage {
	const TYPE_CODE = 0x43;
	/**
	 * @var string
	 */
	private $blobId;
	/**
	 * @var int
	 */
	private $blobLength;
	/**
	 * @var string
	 */
	private $encryptionKey;

	/**
	 * @param GroupId $groupId
	 * @param string $blobId
	 * @param int $blobLength
	 * @param string $encryptionKey
	 */
	function __construct(GroupId $groupId, $blobId, $blobLength, $encryptionKey) {
		parent::__construct($groupId);
		$this->blobId = $blobId;
		$this->blobLength = $blobLength;
		$this->encryptionKey = $encryptionKey;
	}

	/**
	 * @return string
	 */
	public function getBlobId()
	{
		return $this->blobId;
	}

	/**
	 * @return int
	 */
	public function getBlobLength()
	{
		return $this->blobLength;
	}

	/**
	 * @return string
	 */
	public function getEncryptionKey()
	{
		return $this->encryptionKey;
	}

	/**
	 * @return string
	 */
	function __toString() {
		return 'group image message';
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
