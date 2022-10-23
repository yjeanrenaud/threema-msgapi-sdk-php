<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

use Threema\MsgApi\Types\GroupId;

class GroupVideoMessage extends ThreemaGroupMessage {
	const TYPE_CODE = 0x44;
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
	 * @var string
	 */
	private $thumbnailBlobId;

	/**
	 * @var int
	 */
	private $thumbnailBlobLength;

	/**
	 * @var int
	 */
	private $duration;

	/**
	 * @param GroupId $groupId
	 * @param string $blobId
	 * @param int $blobLength
	 * @param string $thumbnailBlobId
	 * @param int $thumbnailBlobLength
	 * @param string $encryptionKey
	 * @param int $duration
	 */
	function __construct(GroupId $groupId, $blobId, $blobLength, $thumbnailBlobId, $thumbnailBlobLength, $encryptionKey, $duration) {
		parent::__construct($groupId);
		$this->blobId = $blobId;
		$this->blobLength = $blobLength;
		$this->encryptionKey = $encryptionKey;
		$this->thumbnailBlobId = $thumbnailBlobId;
		$this->thumbnailBlobLength = $thumbnailBlobLength;
		$this->duration = $duration;
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
	public function getThumbnailBlobId()
	{
		return $this->thumbnailBlobId;
	}

	/**
	 * @return int
	 */
	public function getThumbnailBlobLength()
	{
		return $this->thumbnailBlobLength;
	}

	/**
	 * @return int
	 */
	public function getDuration()
	{
		return $this->duration;
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
		return 'group video message';
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
