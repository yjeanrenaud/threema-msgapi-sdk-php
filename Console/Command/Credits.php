<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\Console\Command;

use Threema\Console\Common;
use Threema\MsgApi\Connection;
use Threema\MsgApi\ConnectionSettings;
use Threema\MsgApi\PublicKeyStore;

class Credits extends Base {
	/**
	 * @var PublicKeyStore
	 */
	private $publicKeyStore;

	/**
	 * @param PublicKeyStore $publicKeyStore
	 */
	function __construct(PublicKeyStore $publicKeyStore) {
		parent::__construct('Get remaining credits',
			array(self::argFrom, self::argSecret),
			'Get the remaining credits');
		$this->publicKeyStore = $publicKeyStore;
	}

	function doRun() {
		$from = $this->getArgumentThreemaId(self::argFrom);
		$secret = $this->getArgument(self::argSecret);

		Common::required($from, $secret);

		//define connection settings
		$settings = new ConnectionSettings($from, $secret);

		//create a connection
		$connector = new Connection($settings, $this->publicKeyStore);

		$result = $connector->credits();
		Common::required($result);
		if($result->isSuccess()) {
			Common::l("remaining credits: ".$result->getCredits());
		}
		else {
			Common::e($result->getErrorMessage());
		}
	}
}
