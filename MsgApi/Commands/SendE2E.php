<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

use Threema\MsgApi\Commands\Results\SendE2EResult;

class SendE2E implements CommandInterface {
	/**
	 * @var string
	 */
	private $nonce;

	/**
	 * @var string
	 */
	private $box;

	/**
	 * @var string
	 */
	private $threemaId;
	/**
	 * @var array
	 */
	private $moreOptions;

	/**
	 * @param string $threemaId
	 * @param string $nonce
	 * @param string $box
	 */
	function __construct($threemaId, $nonce, $box, array $moreOptions = null) {
		$this->nonce = $nonce;
		$this->box = $box;
		$this->threemaId = $threemaId;
		$this->moreOptions = null === $moreOptions ? [] : $moreOptions;
	}

	/**
	 * @return string
	 */
	public function getNonce() {
		return $this->nonce;
	}

	/**
	 * @return string
	 */
	public function getBox() {
		return $this->box;
	}

	/**
	 * @return array
	 */
	function getParams() {
		$p['to'] = $this->threemaId;
		$p['nonce'] = bin2hex($this->getNonce());
		$p['box'] = bin2hex($this->getBox());
		return array_merge($this->moreOptions, $p);
	}

	/**
	 * @return string
	 */
	function getPath() {
		return 'send_e2e';
	}

	/**
	 * @param int $httpCode
	 * @param object $res
	 * @return SendE2EResult
	 */
	function parseResult($httpCode, $res){
		return new SendE2EResult($httpCode, $res);
	}
}

