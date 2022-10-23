<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Tools;

class FileAnalysisTool {
	/**
	 * @param string $file
	 * @param bool $readExifTags (only needed in ImageMessage receives)
	 * @return FileAnalysisResult
	 */
	public function analyse(string $file, bool $readExifTags = false): ?FileAnalysisResult {
		//check if file exists
		if(false === file_exists($file)) {
			return null;
		}

		//is not a file
		if(false === is_file($file)) {
			return null;
		}

		//get file size
		$size = filesize($file);

		$mimeType = null;
		//mime type getter
		if(function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mimeType = finfo_file($finfo, $file);
		}
		else if(function_exists('mime_content_type')) {
			$mimeType = mime_content_type($file);
		}

		//default mime type
		if(strlen($mimeType) == 0) {
			//default mime type
			$mimeType = 'application/octet-stream';
		}

		// try to get caption
		$exifTags = [];
		if (true === $readExifTags && function_exists('exif_read_data')) {
			$exifTags = exif_read_data($file);
		}
		return new FileAnalysisResult($mimeType, $size, $file, $exifTags);
	}

	/**
	 * @param $file
	 * @return bool|string
	 */
	public function getContent($file) {
		return file_get_contents($file);
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	public function isImage($file) {
		$r = $this->analyse($file);
		return $r !== null && in_array($r->getMimeType(), [
				'image/jpg',
				'image/jpeg',
				'image/png'
			]);
	}
}
