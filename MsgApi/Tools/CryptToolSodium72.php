<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Tools;

use Threema\MsgApi\Core\Exception;
use Threema\MsgApi\Core\KeyPair;
use function Sodium\randombytes_buf;

/**
 * Contains static methods to do various Threema cryptography related tasks.
 * Support PHP sodium
 *
 * @package Threema\MsgApi\Core
 */
class CryptToolSodium72 extends  CryptTool {
	/**
	 * @param string $data
	 * @param string $nonce
	 * @param string $senderPrivateKey
	 * @param string $recipientPublicKey
	 * @return string encrypted box
	 */
	protected function makeBox($data, $nonce, $senderPrivateKey, $recipientPublicKey) {
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$kp = sodium_crypto_box_keypair_from_secretkey_and_publickey($senderPrivateKey, $recipientPublicKey);

		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return sodium_crypto_box($data, $nonce, $kp);
	}

	/**
	 * make a secret box
	 *
	 * @param $data
	 * @param $nonce
	 * @param $key
	 * @return mixed
	 */
	public function makeSecretBox($data, $nonce, $key) {
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return sodium_crypto_secretbox($data, $nonce, $key);
	}


	/**
	 * @param string $box
	 * @param string $recipientPrivateKey
	 * @param string $senderPublicKey
	 * @param string $nonce
	 * @return null|string
	 */
	public function openBox($box, $recipientPrivateKey, $senderPublicKey, $nonce) {
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$kp = sodium_crypto_box_keypair_from_secretkey_and_publickey($recipientPrivateKey, $senderPublicKey);
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return sodium_crypto_box_open($box, $nonce, $kp);
	}

	/**
	 * decrypt a secret box
	 *
	 * @param string $box as binary
	 * @param string $nonce as binary
	 * @param string $key as binary
	 * @return string as binary
	 */
	public function openSecretBox($box, $nonce, $key) {
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return sodium_crypto_secretbox_open($box, $nonce, $key);
	}

	/**
	 * Generate a new key pair.
	 *
	 * @return KeyPair the new key pair
	 */
	final public function generateKeyPair() {
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		$kp = sodium_crypto_box_keypair();
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return new KeyPair(sodium_crypto_box_secretkey($kp), sodium_crypto_box_publickey($kp));
	}

	/**
	 * @param int $size
	 * @return string
	 */
	protected function createRandom($size) {
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return \random_bytes($size);
	}

	/**
	 * Derive the public key
	 *
	 * @param string $privateKey in binary
	 * @return string public key as binary
	 */
	final public function derivePublicKey($privateKey) {
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return sodium_crypto_box_publickey_from_secretkey($privateKey);
	}

	/**
	 * Check if implementation supported
	 * @return bool
	 */
	public function isSupported() {
		return true === extension_loaded('sodium')
			&& true === function_exists('sodium_crypto_box_open');
	}

	/**
	 * Validate crypt tool
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function validate() {
		if(false === $this->isSupported()) {
			throw new Exception('Sodium implementation not supported');
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'sodium';
	}

	/**
	 * Description of the CryptTool
	 * @return string
	 */
	public function getDescription() {
		/** @noinspection PhpUndefinedNamespaceInspection @noinspection PhpUndefinedFunctionInspection */
		return 'PHP Sodium implementation';
	}
}
