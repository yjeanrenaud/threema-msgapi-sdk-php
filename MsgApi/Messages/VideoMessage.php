<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

class VideoMessage extends ThreemaMessage {
	const TYPE_CODE = 0x13;

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
	 * @var int
	 */
	private $duration;
	/**
	 * @var
	 */
	private $blobLength;
	/**
	 * @var
	 */
	private $thumbnailLength;

	/**
	 * @param string $blobId
	 * @param $blobLength
	 * @param string $thumbnailBlobId
	 * @param $thumbnailLength
	 * @param string $encryptionKey
	 * @param int $duration
	 */
	function __construct($blobId, $blobLength, $thumbnailBlobId, $thumbnailLength, $encryptionKey, $duration) {
		$this->blobId = $blobId;
		$this->thumbnailBlobId = $thumbnailBlobId;
		$this->encryptionKey = $encryptionKey;
		$this->duration = $duration;
		$this->blobLength = $blobLength;
		$this->thumbnailLength = $thumbnailLength;
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
	public function getThumbnailBlobId() {
		return $this->thumbnailBlobId;
	}

	/**
	 * @return string
	 */
	public function getDuration() {
		return $this->duration;
	}

	/**
	 * @return mixed
	 */
	public function getBlobLength()
	{
		return $this->blobLength;
	}

	/**
	 * @return mixed
	 */
	public function getThumbnailLength()
	{
		return $this->thumbnailLength;
	}
	/**
	 * @return string
	 */
	function __toString() {
		return 'video message';
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
