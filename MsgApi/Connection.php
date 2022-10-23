<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi;

use Threema\MsgApi\Core\Exception;
use Threema\MsgApi\Core\Url;
use Threema\MsgApi\Commands\Capability;
use Threema\MsgApi\Commands\CommandInterface;
use Threema\MsgApi\Commands\Credits;
use Threema\MsgApi\Commands\DownloadFile;
use Threema\MsgApi\Commands\FetchPublicKey;
use Threema\MsgApi\Commands\JsonCommandInterface;
use Threema\MsgApi\Commands\JsonPartCommandInterface;
use Threema\MsgApi\Commands\LookupEmail;
use Threema\MsgApi\Commands\LookupIdBulk;
use Threema\MsgApi\Commands\LookupPhone;
use Threema\MsgApi\Commands\MultiPartCommandInterface;
use Threema\MsgApi\Commands\Results\CapabilityResult;
use Threema\MsgApi\Commands\Results\DownloadFileResult;
use Threema\MsgApi\Commands\Results\FetchPublicKeyResult;
use Threema\MsgApi\Commands\Results\LookupBulkIdResult;
use Threema\MsgApi\Commands\Results\LookupIdResult;
use Threema\MsgApi\Commands\Results\Result;
use Threema\MsgApi\Commands\Results\SendSimpleResult;
use Threema\MsgApi\Commands\Results\SendE2EResult;
use Threema\MsgApi\Commands\Results\UploadFileResult;
use Threema\MsgApi\Commands\SendSimple;
use Threema\MsgApi\Commands\SendE2E;
use Threema\MsgApi\Commands\UploadFile;
use Threema\MsgApi\Tools\CryptTool;

/**
 * Class Connection
 * @package Threema\MsgApi
 */
class Connection
{
	/**
	 * @var ConnectionSettings
	 */
	private $setting;

	/**
	 * @var PublicKeyStore
	 */
	private $publicKeyStore;

	/**
	 * @param ConnectionSettings $setting
	 * @param PublicKeyStore $publicKeyStore stores the public keys locally to save network traffic
	 */
	public function __construct(ConnectionSettings $setting, PublicKeyStore $publicKeyStore = null) {
		$this->setting = $setting;
		$this->publicKeyStore = $publicKeyStore;
	}

	/**
	 * @param Receiver $receiver
	 * @param $text
	 * @return SendSimpleResult
	 */
	public function sendSimple(Receiver $receiver, string $text) {
		$command = new SendSimple($receiver, $text);
		return $this->post($command);
	}

	/**
	 * @param string $threemaId
	 * @param string $nonce
	 * @param string $box
	 * @param array|null $moreOptions
	 * @return SendE2EResult
	 */
	public function sendE2E(string $threemaId, string $nonce, string $box, array $moreOptions = null): SendE2EResult {
		$command = new SendE2E($threemaId, $nonce, $box, $moreOptions);
		return $this->post($command, $moreOptions);
	}

	/**
	 * @param $encryptedFileData (binary string)
	 * @param array|null $moreOptions
	 * @return UploadFileResult
	 */
	public function uploadFile(string $encryptedFileData,  array $moreOptions = null): UploadFileResult {
		$command = new UploadFile($encryptedFileData);
		return $this->postMultiPart($command, $moreOptions);
	}


	/**
	 * @param $blobId
	 * @param callable $progress
	 * @return DownloadFileResult
	 */
	public function downloadFile(string $blobId, \Closure $progress = null): DownloadFileResult {
		$command = new DownloadFile($blobId);
		return $this->get($command, $progress);
	}

	/**
	 * @param $phoneNumber
	 * @return LookupIdResult
	 */
	public function keyLookupByPhoneNumber(string $phoneNumber): LookupIdResult {
		$command = new LookupPhone($phoneNumber);
		return $this->get($command);
	}

	/**
	 * @param string $email
	 * @return LookupIdResult
	 */
	public function keyLookupByEmail(string $email): LookupIdResult {
		$command = new LookupEmail($email);
		return $this->get($command);
	}

	/**
	 * @param array $emails
	 * @param array $phoneNumbers
	 * @param bool $hashed
	 * @return LookupBulkIdResult
	 */
	public function keyLookupBulk(array $emails, array $phoneNumbers, ?bool $hashed = true): LookupBulkIdResult {
		$command = new LookupIdBulk(
				$emails,
				$phoneNumbers,
				$hashed,
				CryptTool::getInstance());
		return $this->postJson($command);
	}
	/**
	 * @param string $threemaId valid threema id (8 Chars)
	 * @return CapabilityResult
	 */
	public function keyCapability($threemaId) {
		return $this->get(new Capability($threemaId));
	}


	/**
	 * @return CreditsResult
	 */
	public function credits() {
		return $this->get(new Credits());
	}

	/**
	 * @param $threemaId
	 * @return FetchPublicKeyResult
	 */
	public function fetchPublicKey($threemaId) {
		$publicKey = null;

		if (null !== $this->publicKeyStore) {
			$publicKey = $this->publicKeyStore->getPublicKey($threemaId);
		}

		if (null === $publicKey) {
			$command = new FetchPublicKey($threemaId);
			$result = $this->get($command);
			if (false === $result->isSuccess()) {
				return $result;
			}
			$publicKey = $result->getRawResponse();

			if (null !== $this->publicKeyStore) {
				$this->publicKeyStore->setPublicKey($threemaId, $publicKey);
			}
		}

		//create a key result
		return new FetchPublicKeyResult(200, $publicKey);
	}

	/**
	 * @param callable $progress
	 * @return array
	 */
	private function createDefaultOptions(\Closure $progress = null) {
		$options = array(
			CURLOPT_RETURNTRANSFER => true
		);

		//no progress
		if (null !== $progress) {
			$options[CURLOPT_NOPROGRESS] = false;
			$options[CURLOPT_PROGRESSFUNCTION] = $progress;
		}

		//tls settings

		if (true === $this->setting->getTlsOption(ConnectionSettings::tlsOptionForceHttps, false)) {
			//limit allowed protocols to HTTPS
			$options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
		}
		if ($tlsVersion = $this->setting->getTlsOption(ConnectionSettings::tlsOptionVersion)) {
			if (is_int($tlsVersion)) {
				//if number is given use it
				$options[CURLOPT_SSLVERSION] = $tlsVersion;
			} else {
				//interpret strings as TLS versions
				switch ($tlsVersion) {
					case '1.0':
						$options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_0;
						break;
					case '1.1':
						$options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_1;
						break;
					case '1.2':
						$options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
						break;
					default:
						$options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_DEFAULT;
						break;
				}
			}
		}
		if ($tlsCipher = $this->setting->getTlsOption(ConnectionSettings::tlsOptionCipher, null)) {
			if(true === is_string($tlsCipher)) {
				$options[CURLOPT_SSL_CIPHER_LIST] = $tlsCipher;
			}
		}
		return $options;
	}

	/**
	 * @param array $params
	 * @param array|null $more
	 * @return array
	 */
	private function processRequestParams(array $params, array $more = null) {
		if (null === $params) {
			$params = array();
		}

		$params['from'] = $this->setting->getThreemaId();
		$params['secret'] = $this->setting->getSecret();

		if (null !== $more) {
			$params = array_merge($params, $more);
		}

		return $params;
	}

	/**
	 * @param CommandInterface $command
	 * @param callable $progress
	 * @return Result
	 */
	protected function get(CommandInterface $command, \Closure $progress = null) {
		$params = $this->processRequestParams($command->getParams());
		return $this->call($command->getPath(),
			$this->createDefaultOptions($progress),
			$params,
			function ($httpCode, $response) use ($command) {
				return $command->parseResult($httpCode, $response);
			});
	}

	/**
	 * @param CommandInterface $command
	 * @return Result
	 */
	protected function post(CommandInterface $command) {
		$options = $this->createDefaultOptions();
		$params = $this->processRequestParams($command->getParams());

		$options[CURLOPT_POST] = true;
		$options[CURLOPT_POSTFIELDS] = http_build_query($params);
		$options[CURLOPT_HTTPHEADER] = array(
			'Content-Type: application/x-www-form-urlencoded');

		return $this->call($command->getPath(), $options, null, function ($httpCode, $response) use ($command) {
			return $command->parseResult($httpCode, $response);
		});
	}

	/**
	 * @param MultiPartCommandInterface $command
	 * @param array|null $moreOptions
	 * @return Result
	 * @throws Exception
	 */
	protected function postMultiPart(MultiPartCommandInterface $command, array $moreOptions = null) {
		$options = $this->createDefaultOptions();
		$params = $this->processRequestParams($command->getParams(), $moreOptions);

		$options[CURLOPT_POST] = true;
		$options[CURLOPT_HTTPHEADER] = array('Content-Type: multipart/form-data');
		$options[CURLOPT_SAFE_UPLOAD] = true;
		$options[CURLOPT_POSTFIELDS] = array_merge([
			'blob' => $command->getData()
		], $command->getParams());

		return $this->call($command->getPath(), $options, $params, function ($httpCode, $response) use ($command) {
			return $command->parseResult($httpCode, $response);
		});
	}

	/**
	 * @param JsonCommandInterface $command
	 * @return Result
	 */
	protected function postJson(JsonCommandInterface $command) {
		$options = $this->createDefaultOptions();
		$params = $this->processRequestParams($command->getParams());

		$options[CURLOPT_POST] = true;
		$options[CURLOPT_HTTPHEADER] = array('Content-Type: application/json');
		$options[CURLOPT_SAFE_UPLOAD] = true;
		$options[CURLOPT_POSTFIELDS] = json_encode($command->getData());

		return $this->call($command->getPath(), $options, $params, function ($httpCode, $response) use ($command) {
			return $command->parseResult($httpCode, $response);
		});
	}

	/**
	 * @param string $path
	 * @param array $curlOptions
	 * @param array $parameters
	 * @param callable $result
	 * @return mixed
	 * @throws \Threema\MsgApi\Core\Exception
	 */
	private function call($path, array $curlOptions, array $parameters = null, \Closure $result = null) {
		$fullPath = new Url('', $this->setting->getHost());
		$fullPath->addPath($path);

		if (null !== $parameters && count($parameters)) {
			foreach ($parameters as $key => $value) {
				$fullPath->setValue($key, $value);
			}
		}
		$session = curl_init($fullPath->getFullPath());
		curl_setopt_array($session, $curlOptions);

		$response = curl_exec($session);
		if (false === $response) {
			throw new Exception($path . ' ' . curl_error($session));
		}

		$httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
		if (null === $result && $httpCode != 200) {
			throw new Exception($httpCode);
		}

		if (null !== $result) {
			return $result->__invoke($httpCode, $response);
		} else {
			return $response;
		}
	}
}
