<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

use Threema\MsgApi\Types\GroupId;

class GroupFileMessage extends ThreemaGroupMessage {
	const TYPE_CODE = 0x46;

	/**
	 * @var string
	 */
	private $blobId;

	/**
	 * @var string
	 */
	private $thumbnailBlobId;

	/**
	 * @var string
	 */
	private $encryptionKey;

	/**
	 * @var string
	 */
	private $mimeType;

	/**
	 * @var string
	 */
	private $filename;

	/**
	 * @var int
	 */
	private $blobLength;

	/**
	 * @var string
	 */
	private $caption;

	/**
	 * @param GroupId $groupId
	 * @param string $blobId
	 * @param string $thumbnailBlobId
	 * @param string $encryptionKey
	 * @param string $mimeType
	 * @param string $filename
	 * @param int $blobLength
	 * @param string $caption
	 */
	function __construct(GroupId $groupId, $blobId, $thumbnailBlobId, $encryptionKey, $mimeType, $filename, $blobLength, $caption) {
		parent::__construct($groupId);
		$this->blobId = $blobId;
		$this->thumbnailBlobId = $thumbnailBlobId;
		$this->encryptionKey = $encryptionKey;
		$this->mimeType = $mimeType;
		$this->filename = $filename;
		$this->blobLength = $blobLength;
		$this->caption = $caption;
	}

	/**
	 * @return string
	 */
	public function getBlobId() {
		return $this->blobId;
	}

	/**
	 * @return string
	 */
	public function getEncryptionKey() {
		return $this->encryptionKey;
	}

	/**
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * @return string
	 */
	public function getMimeType() {
		return $this->mimeType;
	}

	/**
	 * @return int
	 */
	public function getBlobLength() {
		return $this->blobLength;
	}

	/**
	 * @return string
	 */
	public function getThumbnailBlobId() {
		return $this->thumbnailBlobId;
	}

	/**
	 * @return string
	 */
	public function getCaption() {
		return $this->caption;
	}
	/**
	 * @return string
	 */
	function __toString() {
		return 'group file message';
	}

	/**
	 * Get the message type code of this message.
	 *
	 * @return int message type code
	 */
	public final function getTypeCode() {
		return self::TYPE_CODE;
	}
}
