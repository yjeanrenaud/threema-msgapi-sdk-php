<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

class AudioMessage extends ThreemaMessage {
	const TYPE_CODE = 0x14;

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
	 * @var string
	 */
	private $blobId;


	/**
	 * @param string $blobId
	 * @param $blobLength
	 * @param string $encryptionKey
	 * @param int $duration
	 */
	function __construct($blobId, $blobLength, $encryptionKey, $duration) {
		$this->blobId = $blobId;
		$this->encryptionKey = $encryptionKey;
		$this->duration = $duration;
		$this->blobLength = $blobLength;
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
	 * @return string
	 */
	function __toString() {
		return 'audio message';
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
