<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Commands;

use Threema\MsgApi\Commands\Results\UploadFileResult;

class UploadFile implements MultiPartCommandInterface {
	/**
	 * @var string
	 */
	private $encryptedFileData;
	/**
	 * @var bool|null
	 */
	private $persist;

	/**
	 * @param string $encryptedFileData (binary) the encrypted file data
	 * @param bool|null $persist
	 */
	function __construct($encryptedFileData, ?bool $persist = false) {
		$this->encryptedFileData = $encryptedFileData;
		$this->persist = $persist;
	}

	/**
	 * @return array
	 */
	function getParams(): array {
		if (true === $this->persist) {
			return ['persist' => 1];
		}
		return [];
	}

	/**
	 * @return string
	 */
	function getPath(): string {
		return 'upload_blob';
	}

	/**
	 * @return string
	 */
	function getData(): string {
		return $this->encryptedFileData;
	}

	/**
	 * @param int $httpCode
	 * @param object $res
	 * @return UploadFileResult
	 */
	function parseResult($httpCode, $res): UploadFileResult {
		return new UploadFileResult($httpCode, $res);
	}
}
