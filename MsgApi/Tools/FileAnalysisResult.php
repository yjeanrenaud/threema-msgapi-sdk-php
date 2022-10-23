<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Tools;

class FileAnalysisResult {
	/**
	 * @var string
	 */
	private $mimeType;

	/**
	 * @var int
	 */
	private $size;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var array
	 */
	private $exifTags;

	/**
	 * @param string $mimeType
	 * @param int $size
	 * @param string $path
	 * @param array $exifTags
	 */
	public function __construct($mimeType, $size, $path, array $exifTags = []) {
		$this->mimeType = $mimeType;
		$this->size = $size;
		$this->path = realpath($path);
		$this->path = $path;
		$this->exifTags = $exifTags;
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
	public function getSize() {
		return $this->size;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getFileName() {
		return basename($this->path);
	}

	/**
	 * @return array
	 */
	public function getExifTags() {
		return $this->exifTags;
	}
}
