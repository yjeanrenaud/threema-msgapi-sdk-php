<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

use Threema\MsgApi\Commands\Results\DownloadFileResult;
use Threema\MsgApi\Commands\Results\UploadFileResult;

class DownloadFile implements CommandInterface {
	/**
	 * @var string
	 */
	private $blobId;

	/**
	 * @param string $blobId
	 */
	function __construct($blobId) {
		$this->blobId = $blobId;
	}

	/**
	 * @return array
	 */
	function getParams() {
		return [];
	}

	/**
	 * @return string
	 */
	function getPath() {
		return 'blobs/'.$this->blobId;
	}

	/**
	 * @param int $httpCode
	 * @param object $res
	 * @return UploadFileResult
	 */
	function parseResult($httpCode, $res){
		return new DownloadFileResult($httpCode, $res);
	}
}
