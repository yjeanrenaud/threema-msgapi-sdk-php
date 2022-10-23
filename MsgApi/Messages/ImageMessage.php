<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

class ImageMessage extends ThreemaMessage {
	const TYPE_CODE = 0x02;

	/**
	 * @var string
	 */
	private $blobId;

	/**
	 * @var string
	 */
	private $length;

	/**
	 * @var int
	 */
	private $nonce;

	/**
	 * @var string
	 */
	private $caption;

	/**
	 * @param string $blobId
	 * @param int $length
	 * @param string $nonce
	 */
	function __construct($blobId, $length, $nonce) {
		$this->blobId = $blobId;
		$this->length = $length;
		$this->nonce = $nonce;
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
	public function getLength() {
		return $this->length;
	}

	/**
	 * @return int
	 */
	public function getNonce() {
		return $this->nonce;
	}

	/**
	 * @param string $caption
	 */
	public function setCaption($caption) {
		$this->caption = $caption;
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
		return 'image message';
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
