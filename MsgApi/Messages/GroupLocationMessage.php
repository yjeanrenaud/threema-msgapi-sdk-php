<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Messages;

use Threema\MsgApi\Types\GroupId;

class GroupLocationMessage extends ThreemaGroupMessage {
	const TYPE_CODE = 0x42;
	/**
	 * @var float
	 */
	private $latitude;
	/**
	 * @var float
	 */
	private $longitude;
	/**
	 * @var float
	 */
	private $accuracy;
	/**
	 * @var string
	 */
	private $poiName;
	/**
	 * @var string
	 */
	private $poiAddress;


	/**
	 * @param GroupId $groupId
	 * @param double $latitude
	 * @param double $longitude
	 * @param double $accuracy
	 * @param string $poiName
	 * @param string $poiAddress
	 */
	function __construct(GroupId $groupId, $latitude, $longitude, $accuracy, $poiName, $poiAddress) {
		parent::__construct($groupId);
		$this->latitude = $latitude;
		$this->longitude = $longitude;
		$this->accuracy = $accuracy;
		$this->poiName = $poiName;
		$this->poiAddress = $poiAddress;
	}

	/**
	 * @return float
	 */
	public function getLatitude()
	{
		return $this->latitude;
	}

	/**
	 * @return float
	 */
	public function getLongitude()
	{
		return $this->longitude;
	}

	/**
	 * @return float
	 */
	public function getAccuracy()
	{
		return $this->accuracy;
	}

	/**
	 * @return string
	 */
	public function getPoiName()
	{
		return $this->poiName;
	}

	/**
	 * @return string
	 */
	public function getPoiAddress()
	{
		return $this->poiAddress;
	}

	/**
	 * @return string
	 */
	function __toString() {
		return 'group location message';
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
