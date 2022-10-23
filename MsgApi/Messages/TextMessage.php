<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

class TextMessage extends ThreemaMessage {
	const TYPE_CODE = 0x01;

	/**
	 * @var string
	 */
	private $text;

	/**
	 * @param string $text
	 */
	function __construct($text) {
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
		return 'text message';
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
