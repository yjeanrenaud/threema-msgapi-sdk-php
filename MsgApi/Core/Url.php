<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en
 */

namespace Threema\MsgApi\Core;

class Url {
	/**
	 * @var string[]
	 */
	private $values = array();

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var string
	 */
	private $host;

	/**
	 * @param string $path
	 * @param string $host
	 */
	public function __construct($path, $host = null)
	{
		$this->path = $path;
		$this->host = $host;
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return $this
	 */
	public function setValue($key, $value){
		$this->values[$key] = $value;
		return $this;
	}

	/**
	 * Add a path to the current url
	 *
	 * @param string $path
	 * @return $this
	 */
	public function addPath($path) {
		while(substr($this->path, strlen($this->path)-1) == '/') {
			$this->path = substr($this->path, 0, strlen($this->path)-1);
		}

		$realPath = '';
		foreach(explode('/', $path) as $c => $pathPiece) {
			if($c > 0) {
				$realPath .= '/';
			}
			$realPath .= urlencode($pathPiece);
		}
		while(substr($path, 0, 1) == '/') {
			$path = substr($path, 1);
		}

		$this->path .= '/'.$path;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPath() {
		$p = $this->path;
		if(count($this->values) > 0) {
			$s = http_build_query($this->values);
			if(strlen($s) > 0) {
				$p .= '?'.$s;
			}
		}

		return $p;
	}


	function __toString() {
		return $this->getPath();
	}

	/**
	 * @return string
	 */
	public function getFullPath() {
		return $this->host.(substr($this->getPath(), 0, 1) == '/' ? '' : '/').$this->getPath();
	}

	public static function parametersToArray($urlParameter) {
		$result = array();

		while(strlen($urlParameter) > 0) {
			// name
			$keyPosition= strpos($urlParameter,'=');
			$keyValue = substr($urlParameter,0,$keyPosition);
			// value
			$valuePosition = strpos($urlParameter,'&') ? strpos($urlParameter,'&'): strlen($urlParameter);
			$valueValue = substr($urlParameter,$keyPosition+1,$valuePosition-$keyPosition-1);

			// decoding the response
			$result[$keyValue] = urldecode($valueValue);
			$urlParameter = substr($urlParameter,$valuePosition+1,strlen($urlParameter));
		}

		return $result;
	}

	public static function fromString($path) {
		$result = parse_url($path);

		if (true === array_key_exists('scheme', $result)
			&& true === array_key_exists('host', $result)) {
			return new Url($result['path'], $result['scheme'].'://'.$result['host']);
		}

		return new Url($path);

	}
}
