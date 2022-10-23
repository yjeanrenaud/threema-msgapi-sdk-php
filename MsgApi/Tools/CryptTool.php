<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Tools;

use Salt;
use Threema\MsgApi\Core\Exception;
use Threema\MsgApi\Core\KeyPair;
use Threema\MsgApi\Core\AssocArray;
use Threema\MsgApi\Commands\Results\UploadFileResult;
use Threema\MsgApi\Exceptions\BadMessageException;
use Threema\MsgApi\Exceptions\DecryptionFailedException;
use Threema\MsgApi\Exceptions\InvalidArgumentException;
use Threema\MsgApi\Exceptions\UnsupportedMessageTypeException;
use Threema\MsgApi\Messages;
use Threema\MsgApi\Types\GroupId;

/**
 * Interface CryptTool
 * Contains static methods to do various Threema cryptography related tasks.
 *
 * @package Threema\MsgApi\Tool
 */
abstract class CryptTool {
	const TYPE_SODIUM = 'sodium';
	const TYPE_SALT = 'salt';

	const FILE_NONCE = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01";
	const FILE_THUMBNAIL_NONCE = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x02";

	/**
	 * @var CryptTool
	 */
	private static $instance = null;

	/**
	 * Manually set or clear the cryptTool instance
	 * @param CryptTool $tool
	 */
	public static function setInstance(CryptTool $tool = null) {
		self::$instance = $tool;
	}
	/**
	 * Prior libsodium
	 *
	 * @return CryptTool
	 */
	public static function getInstance() {
		if(null === self::$instance) {
			foreach([
				function() {
					return self::createInstance(self::TYPE_SODIUM);
				},
				function() {
					return self::createInstance(self::TYPE_SALT);
				}] as $instanceGenerator) {
				$i = $instanceGenerator->__invoke();
				if(null !== $i) {
					self::setInstance($i);
					break;
				}
			}
		}

		return self::$instance;
	}

	/**
	 * @param string $type
	 * @return null|CryptTool null on unknown type
	 */
	public static function createInstance($type) {
		switch($type) {
			case self::TYPE_SODIUM:
				$instance = new CryptToolSodium72();
				if (true === $instance->isSupported())
				{
					return $instance;
				}

				// try next
				$instance = new CryptToolSodium();
				if(true === $instance->isSupported()) {
					return $instance;
				}

				//try to instance old version of sodium wrapper
				/** @noinspection PhpDeprecationInspection */
				$instance = new CryptToolSodiumDep();
				return $instance->isSupported() ? $instance :null;
			case self::TYPE_SALT:
				$instance = new CryptToolSalt();
				return $instance->isSupported() ? $instance :null;
			default:
				return null;
		}
	}

	const MESSAGE_ID_LEN = 8;
	const IDENTITY_LEN = 8;
	const GROUP_ID_LEN = 8;
	const BLOB_ID_LEN = 16;
	const FILE_SIZE_LEN = 4;
	const IMAGE_NONCE_LEN = 24;
	const SYMMETRIC_KEY_LEN = 32;

	const EMAIL_HMAC_KEY = "\x30\xa5\x50\x0f\xed\x97\x01\xfa\x6d\xef\xdb\x61\x08\x41\x90\x0f\xeb\xb8\xe4\x30\x88\x1f\x7a\xd8\x16\x82\x62\x64\xec\x09\xba\xd7";
	const PHONENO_HMAC_KEY = "\x85\xad\xf8\x22\x69\x53\xf3\xd9\x6c\xfd\x5d\x09\xbf\x29\x55\x5e\xb9\x55\xfc\xd8\xaa\x5e\xc4\xf9\xfc\xd8\x69\xe2\x58\x37\x07\x23";

	const FILE_RENDERING_TYPE_DEFAULT = 0;
	const FILE_RENDERING_TYPE_MEDIA = 1;
	const FILE_RENDERING_TYPE_STICKER = 2;

	protected  function __construct() {}

	/**
	 * @return array
	 */
	protected function getFileMessageRenderingTypes(): array
	{
		return [
			self::FILE_RENDERING_TYPE_DEFAULT,
			self::FILE_RENDERING_TYPE_MEDIA,
			self::FILE_RENDERING_TYPE_STICKER,
		];
	}

	/**
	 * Encrypt a text message.
	 *
	 * @param string $text the text to be encrypted (max. 3500 bytes)
	 * @param string $senderPrivateKey the private key of the sending ID
	 * @param string $recipientPublicKey the public key of the receiving ID
	 * @param string $nonce the nonce to be used for the encryption (usually 24 random bytes)
	 * @return string encrypted box
	 */
	public function encryptMessageText($text, $senderPrivateKey, $recipientPublicKey, $nonce) {
		return $this->padAndMakeBox (
			Messages\TextMessage::TYPE_CODE,
			$text,
			$nonce,
			$senderPrivateKey,
			$recipientPublicKey
		);
	}

	/**
	 * Encrypt a location message.
	 *
	 * @param string $senderPrivateKey the private key of the sending ID
	 * @param string $recipientPublicKey the public key of the receiving ID
	 * @param string $nonce the nonce to be used for the encryption (usually 24 random bytes)
	 * @param float $lat
	 * @param float $lng
	 * @param float|null $accuracy
	 * @param string|null $poiName
	 * @param string|null $poiAddress
	 * @return string encrypted box
	 */
	public function encryptMessageLocation(
			float $lat,
			float $lng,
			?float $accuracy,
			?string $poiName,
			?string $poiAddress,
			$senderPrivateKey,
			$recipientPublicKey,
			$nonce) {

		$locationString = sprintf('%f,%f,%f', $lat, $lng, $accuracy);
		if (!empty($poiName)) {
			$locationString .= "\n".$poiName;
		}

		if (!empty($poiAddress)) {
			$locationString .= "\n".str_replace("\n", "\\n", $poiAddress);
		}
		return $this->padAndMakeBox (
			Messages\LocationMessage::TYPE_CODE,
			$locationString,
			$nonce,
			$senderPrivateKey,
			$recipientPublicKey
		);
	}

	/**
	 * @param UploadFileResult $uploadFileResult the result of the upload
	 * @param EncryptResult $encryptResult the result of the image encryption
	 * @param string $senderPrivateKey the private key of the sending ID (as binary)
	 * @param string $recipientPublicKey the public key of the receiving ID (as binary)
	 * @param string $nonce the nonce to be used for the encryption (usually 24 random bytes)
	 * @return string
	 */
	public function encryptImageMessage(
			UploadFileResult $uploadFileResult,
			EncryptResult $encryptResult,
			$senderPrivateKey,
			$recipientPublicKey,
			$nonce) {
		$message = hex2bin($uploadFileResult->getBlobId());
		$message .= pack('V', $encryptResult->getSize());
		$message .= $encryptResult->getNonce();

		return $this->padAndMakeBox(
			Messages\ImageMessage::TYPE_CODE,
			$message,
			$nonce,
			$senderPrivateKey,
			$recipientPublicKey
		);
	}

	/**
	 * @param int $duration duration of the video (in seconds)
	 * @param UploadFileResult $uploadFileResult the result of the video file upload
	 * @param UploadFileResult $uploadThumbnailResult the result of the video thumbnail file upload
	 * @param EncryptResult $encryptResult the result of the video encryption
	 * @param EncryptResult $encryptThumbnailResult the result of the thumbnail encryption
	 * @param string $senderPrivateKey the private key of the sending ID (as binary)
	 * @param string $recipientPublicKey the public key of the receiving ID (as binary)
	 * @param string $nonce the nonce to be used for the encryption (usually 24 random bytes)
	 * @return string
	 */
	public function encryptVideoMessage(
		$duration,
		UploadFileResult $uploadFileResult,
		UploadFileResult $uploadThumbnailResult,
		EncryptResult $encryptResult,
		EncryptResult $encryptThumbnailResult,
		$senderPrivateKey,
		$recipientPublicKey,
		$nonce) {

		$message = pack('s', $duration);
		$message .= hex2bin($uploadFileResult->getBlobId());
		$message .= pack('V', $encryptResult->getSize());
		$message .= hex2bin($uploadThumbnailResult->getBlobId());
		$message .= pack('V', $encryptThumbnailResult->getSize());
		$message .= $encryptResult->getKey();

		return $this->padAndMakeBox(
			Messages\VideoMessage::TYPE_CODE,
			$message,
			$nonce,
			$senderPrivateKey,
			$recipientPublicKey);
	}

	/**
	 * @param int $duration duration of the audio (in seconds)
	 * @param UploadFileResult $uploadFileResult the result of the audio file upload
	 * @param EncryptResult $encryptResult the result of the video encryption
	 * @param string $senderPrivateKey the private key of the sending ID (as binary)
	 * @param string $recipientPublicKey the public key of the receiving ID (as binary)
	 * @param string $nonce the nonce to be used for the encryption (usually 24 random bytes)
	 * @return string
	 */
	public function encryptAudioMessage(
		$duration,
		UploadFileResult $uploadFileResult,
		EncryptResult $encryptResult,
		$senderPrivateKey,
		$recipientPublicKey,
		$nonce) {

		$message = pack('s', $duration);
		$message .= hex2bin($uploadFileResult->getBlobId());
		$message .= pack('V', $encryptResult->getSize());
		$message .= $encryptResult->getKey();

		return $this->padAndMakeBox(
			Messages\AudioMessage::TYPE_CODE,
			$message,
			$nonce,
			$senderPrivateKey,
			$recipientPublicKey);
	}

	/**
	 * @param UploadFileResult $uploadFileResult
	 * @param EncryptResult $encryptResult
	 * @param FileAnalysisResult $fileAnalysisResult
	 * @param $senderPrivateKey
	 * @param $recipientPublicKey
	 * @param $nonce
	 * @param UploadFileResult|null $thumbnailUploadFileResult
	 * @param string|null $caption
	 * @param string|null $filename
	 * @param int $renderingType
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function encryptFileMessage(
		UploadFileResult $uploadFileResult,
		EncryptResult $encryptResult,
		?FileAnalysisResult $fileAnalysisResult,
		$senderPrivateKey,
		$recipientPublicKey,
		$nonce,
		?UploadFileResult $thumbnailUploadFileResult = null,
		?string $caption = null,
		?string $filename = null,
		?int $renderingType = null
	) {
		$fileMessageContent = $this->createFileMessageContent(
			$uploadFileResult,
			$encryptResult,
			$fileAnalysisResult,
			$thumbnailUploadFileResult,
			$caption,
			$filename,
			$renderingType
		);

		return $this->padAndMakeBox(
			Messages\FileMessage::TYPE_CODE,
			$fileMessageContent,
			$nonce,
			$senderPrivateKey,
			$recipientPublicKey
		);
	}

	/**
	 * Encrypt a group text message.
	 *
	 * @param GroupId $groupId
	 * @param string $text the text to be encrypted (max. 3500 bytes)
	 * @param string $senderPrivateKey the private key of the sending ID
	 * @param string $recipientPublicKey the public key of the receiving ID
	 * @param string $nonce the nonce to be used for the encryption (usually 24 random bytes)
	 * @return string encrypted box
	 */
	public function encryptGroupMessageText(GroupId $groupId, $text, $senderPrivateKey, $recipientPublicKey, $nonce) {
		return $this->padAndMakeGroupBox(
			$groupId,
			Messages\GroupTextMessage::TYPE_CODE,
			$text,
			$nonce,
			$senderPrivateKey,
			$recipientPublicKey
		);
	}

	/**
	 * Encrypt a group file message.
	 *
	 * @param GroupId $groupId
	 * @param UploadFileResult $uploadFileResult
	 * @param EncryptResult $encryptResult
	 * @param FileAnalysisResult $fileAnalysisResult
	 * @param string $senderPrivateKey the private key of the sending ID
	 * @param string $recipientPublicKey the public key of the receiving ID
	 * @param string $nonce the nonce to be used for the encryption (usually 24 random bytes)
	 * @param UploadFileResult|null $thumbnailUploadFileResult
	 * @param null $caption
	 * @param string|null $filename
	 * @param int $renderingType
	 * @throws InvalidArgumentException
	 * @return string encrypted box
	 */
	public function encryptGroupMessageFile(
		GroupId $groupId,
		UploadFileResult $uploadFileResult,
		EncryptResult $encryptResult,
		FileAnalysisResult $fileAnalysisResult,
		$senderPrivateKey,
		$recipientPublicKey,
		$nonce,
		UploadFileResult $thumbnailUploadFileResult = null,
		$caption = null,
		?string $filename = null,
		?int $renderingType = null
	) {
		$fileMessageContent = $this->createFileMessageContent(
			$uploadFileResult,
			$encryptResult,
			$fileAnalysisResult,
			$thumbnailUploadFileResult,
			$caption,
			$filename,
			$renderingType
		);

		return $this->padAndMakeGroupBox(
			$groupId,
			Messages\GroupFileMessage::TYPE_CODE,
			$fileMessageContent,
			$nonce,
			$senderPrivateKey,
			$recipientPublicKey
		);
	}

	/**
	 * Encrypt a group location message.
	 *
	 * @param GroupId $groupId
	 * @param float $lat
	 * @param float $lng
	 * @param float|null $accuracy
	 * @param string|null $poiName
	 * @param string|null $poiAddress
	 * @param string $senderPrivateKey the private key of the sending ID
	 * @param string $recipientPublicKey the public key of the receiving ID
	 * @param string $nonce the nonce to be used for the encryption (usually 24 random bytes)
	 * @return string encrypted box
	 */
	public function encryptGroupMessageLocation(
		GroupId $groupId,
		float $lat,
		float $lng,
		?float $accuracy,
		?string $poiName,
		?string $poiAddress,
		$senderPrivateKey,
		$recipientPublicKey,
		$nonce) {

		$locationString = sprintf('%f,%f,%f', $lat, $lng, $accuracy);
		if (!empty($poiName)) {
			$locationString .= "\n".$poiName;
		}

		if (!empty($poiAddress)) {
			$locationString .= "\n".str_replace("\n", "\\n", $poiAddress);
		}
		return $this->padAndMakeGroupBox(
			$groupId,
			Messages\GroupLocationMessage::TYPE_CODE,
			$locationString,
			$nonce,
			$senderPrivateKey,
			$recipientPublicKey
		);
	}

	private function createFileMessageContent(
		UploadFileResult $uploadFileResult,
		EncryptResult $encryptResult,
		FileAnalysisResult $fileAnalysisResult,
		?UploadFileResult $thumbnailUploadFileResult = null,
		?string $caption = null,
		?string $filename = null,
		?int $renderingType = null
	): string {
		// set default rendering type, if none is set
		$renderingType = $renderingType ?? self::FILE_RENDERING_TYPE_DEFAULT;
		$deprecatedRenderingType = $renderingType === self::FILE_RENDERING_TYPE_MEDIA ? 1 : 0;

		// check rendering type
		if (!in_array($renderingType, $this->getFileMessageRenderingTypes())) {
			throw new InvalidArgumentException('unhandled rendering type');
		}

		$messageContent = [
			'b' => $uploadFileResult->getBlobId(),
			'k' => bin2hex($encryptResult->getKey()),
			'm' => $fileAnalysisResult->getMimeType(),
			'n' => $filename ?? $fileAnalysisResult->getFileName(),
			's' => $fileAnalysisResult->getSize(),
			'j' => $renderingType,
			'i' => $deprecatedRenderingType
		];

		// Add caption if set
		if (strlen($caption) > 0) {
			$messageContent['d'] = $caption;
		}

		if($thumbnailUploadFileResult != null && strlen($thumbnailUploadFileResult->getBlobId()) > 0) {
			$messageContent['t'] = $thumbnailUploadFileResult->getBlobId();
		}

		return json_encode($messageContent);
	}
	/**
	 * @param string $typeCode (binary)
	 * @param string $messageContent (binary)
	 * @param string $nonce (binary)
	 * @param string $senderPrivateKey (binary)
	 * @param string $recipientPublicKey (binary)
	 * @return string
	 */
	final public function padAndMakeBox($typeCode, $messageContent, $nonce, $senderPrivateKey, $recipientPublicKey)
	{
		/* Append type code */
		$message = pack('C', $typeCode);

		/* Append content */
		$message .= $messageContent;

		/* determine random amount of PKCS7 padding */
		$padbytes = $this->generatePadBytes();

		/* append padding */
		$message .= str_repeat(chr($padbytes), $padbytes);

		return $this->makeBox($message, $nonce, $senderPrivateKey, $recipientPublicKey);

	}

	/**
	 * @param GroupId $groupId
	 * @param string $typeCode (binary)
	 * @param string $messageContent (binary)
	 * @param string $nonce (binary)
	 * @param string $senderPrivateKey (binary)
	 * @param string $recipientPublicKey (binary)
	 * @return string
	 */
	final public function padAndMakeGroupBox(GroupId $groupId, $typeCode, $messageContent, $nonce, $senderPrivateKey, $recipientPublicKey)
	{
		/* Append group identifier */
		$message = $groupId->getGroupCreator();
		$message .= $groupId->getGroupId();

		/* Append content */
		$message .= $messageContent;

		return $this->padAndMakeBox($typeCode, $message, $nonce, $senderPrivateKey, $recipientPublicKey);

	}
	/**
	 * make a box
	 *
	 * @param string $data
	 * @param string $nonce
	 * @param string $senderPrivateKey
	 * @param string $recipientPublicKey
	 * @return string encrypted box
	 */
	abstract protected function makeBox($data, $nonce, $senderPrivateKey, $recipientPublicKey);

	/**
	 * make a secret box
	 *
	 * @param $data
	 * @param $nonce
	 * @param $key
	 * @return mixed
	 */
	abstract public function makeSecretBox($data, $nonce, $key);

	/**
	 * decrypt a box
	 *
	 * @param string $box as binary
	 * @param string $recipientPrivateKey as binary
	 * @param string $senderPublicKey as binary
	 * @param string $nonce as binary
	 * @return string
	 */
	abstract public function openBox($box, $recipientPrivateKey, $senderPublicKey, $nonce);

	/**
	 * decrypt a secret box
	 *
	 * @param string $box as binary
	 * @param string $nonce as binary
	 * @param string $key as binary
	 * @return string as binary
	 */
	abstract public function openSecretBox($box, $nonce, $key);

	/**
	 * @param string $box
	 * @param string $recipientPrivateKey
	 * @param string $senderPublicKey
	 * @param string $nonce
	 * @return Messages\ThreemaMessage the decrypted message
	 * @throws BadMessageException
	 * @throws DecryptionFailedException
	 * @throws Exception
	 * @throws UnsupportedMessageTypeException
	 */
	public function decryptMessage($box, $recipientPrivateKey, $senderPublicKey, $nonce) {

		$data = $this->openBox($box, $recipientPrivateKey, $senderPublicKey, $nonce);

		if (null === $data || strlen($data) == 0) {
			throw new DecryptionFailedException();
		}

		/* remove padding */
		$padbytes = ord($data[strlen($data)-1]);
		$realDataLength = strlen($data) - $padbytes;
		if ($realDataLength < 1) {
			throw new BadMessageException();
		}
		$data = substr($data, 0, $realDataLength);

		/* first byte of data is type */
		$type = ord($data[0]);

		$pos = 1;
		$piece = function($length) use(&$pos, $data) {
			$d = substr($data, $pos, $length);
			$pos += $length;
			return $d;
		};

		switch ($type) {
			case Messages\TextMessage::TYPE_CODE:
				/* Text message */
				if ($realDataLength < 2) {
					throw new BadMessageException();
				}

				return new Messages\TextMessage(substr($data, 1));

			case Messages\DeliveryReceipt::TYPE_CODE:
				/* Delivery receipt */
				if ($realDataLength < (self::MESSAGE_ID_LEN-2) || (($realDataLength - 2) % self::MESSAGE_ID_LEN) != 0)  {
					throw new BadMessageException();
				}

				$receiptType = ord($data[1]);
				$messageIds = str_split(substr($data, 2), self::MESSAGE_ID_LEN);

				return new Messages\DeliveryReceipt($receiptType, $messageIds);

			case Messages\ImageMessage::TYPE_CODE:
				/* Image Message */
				if ($realDataLength != 1 + self::BLOB_ID_LEN + self::FILE_SIZE_LEN + self::IMAGE_NONCE_LEN)  {
					throw new BadMessageException();
				}

				$blobId = $piece->__invoke(self::BLOB_ID_LEN);
				$length = $piece->__invoke(self::FILE_SIZE_LEN);
				$nonce = $piece->__invoke(self::IMAGE_NONCE_LEN);
				return new Messages\ImageMessage(bin2hex($blobId), bin2hex($length), $nonce);

			case Messages\VideoMessage::TYPE_CODE:
				/* Video Message */
				if ($realDataLength != 1 + 2
					+ self::BLOB_ID_LEN + self::FILE_SIZE_LEN
					+ self::BLOB_ID_LEN + self::FILE_SIZE_LEN
					+ self::SYMMETRIC_KEY_LEN)  {
					throw new BadMessageException();
				}

				$duration = $piece->__invoke(2);
				$blobId = $piece->__invoke(self::BLOB_ID_LEN);
				$blobLength = $piece->__invoke(self::FILE_SIZE_LEN);

				$thumbnailBlobId = $piece->__invoke(self::BLOB_ID_LEN);
				$thumbnailLength = $piece->__invoke(self::FILE_SIZE_LEN);
				$key = $piece->__invoke(self::SYMMETRIC_KEY_LEN);
				return new Messages\VideoMessage(
					bin2hex($blobId), current(unpack('V',($blobLength))),
					bin2hex($thumbnailBlobId), current(unpack('V',($thumbnailLength))),
					bin2hex($key),
					current(unpack('s', $duration)));

			case Messages\AudioMessage::TYPE_CODE:
				/* Audio Message */
				if ($realDataLength != 1 + 2
					+ self::BLOB_ID_LEN + self::FILE_SIZE_LEN
					+ self::SYMMETRIC_KEY_LEN)  {
					throw new BadMessageException();
				}

				$duration = $piece->__invoke(2);
				$blobId = $piece->__invoke(self::BLOB_ID_LEN);
				$blobLength = $piece->__invoke(self::FILE_SIZE_LEN);
				$key = $piece->__invoke(self::SYMMETRIC_KEY_LEN);
				return new Messages\AudioMessage(
					bin2hex($blobId), current(unpack('V',($blobLength))),
					bin2hex($key),
					current(unpack('s', $duration)));

			case Messages\FileMessage::TYPE_CODE:
				/* File Message */
				$decodeResult = json_decode(substr($data, 1), true);
				if(null === $decodeResult || false === $decodeResult) {
					throw new BadMessageException();
				}

				$values = AssocArray::byJsonString(substr($data, 1), array('b', 'k', 'm', 'n', 's'));
				if(null === $values) {
					throw new BadMessageException();
				}

				return new Messages\FileMessage(
					$values->getValue('b'),
					$values->getValue('t'),
					$values->getValue('k'),
					$values->getValue('m'),
					$values->getValue('n'),
					$values->getValue('s'),
					$values->getValue('d'));

			case Messages\LocationMessage::TYPE_CODE:
				/* Location Message */
				if ($realDataLength < 4)  {
					throw new BadMessageException();
				}

				$locationStrings = explode("\n", substr($data, $pos));
				$pieces = explode(',', $locationStrings[0]);

				$latitude = doubleval($pieces[0]);
				$longitude = doubleval($pieces[1]);
				$accuracy = 0;
				if (count($pieces) >= 3)
				{
					$accuracy = doubleval($pieces[2]);
				}

				$poiName = null;
				$poiAddress = null;

				if (count($locationStrings) >= 2)
				{
					$poiName = $locationStrings[1];

					if (count($locationStrings) >= 3)
					{
						$poiAddress = str_replace('\n', "\n", $locationStrings[2]);
					}
				}

				return new Messages\LocationMessage(
					$latitude,
					$longitude,
					$accuracy,
					$poiName,
					$poiAddress);

			// Group messages
			case Messages\GroupTextMessage::TYPE_CODE:

				if ($realDataLength < (1 + self::IDENTITY_LEN + self::GROUP_ID_LEN)) {
					throw new UnsupportedMessageTypeException('invalid group message length');
				}

				$groupId = new GroupId(
					$piece->__invoke(self::IDENTITY_LEN),
					$piece->__invoke(self::GROUP_ID_LEN)
				);

				return new Messages\GroupTextMessage($groupId, substr($data, $pos));

			case Messages\GroupLeaveMessage::TYPE_CODE:
				if ($realDataLength !== (1 + self::IDENTITY_LEN + self::GROUP_ID_LEN)) {
					throw new UnsupportedMessageTypeException('invalid group leave message length');
				}
				$groupId = new GroupId(
					$piece->__invoke(self::IDENTITY_LEN),
					$piece->__invoke(self::GROUP_ID_LEN)
				);
				return new Messages\GroupLeaveMessage($groupId);

			case Messages\GroupRequestSyncMessage::TYPE_CODE:
				if ($realDataLength !== (1 + self::GROUP_ID_LEN)) {
					throw new UnsupportedMessageTypeException('invalid group request sync message length');
				}
				$groupId = new GroupId(
				// Creator not set
					null,
					$piece->__invoke(self::GROUP_ID_LEN)
				);
				return new Messages\GroupRequestSyncMessage($groupId);

			case Messages\GroupImageMessage::TYPE_CODE:
				if ($realDataLength !== (1 + self::IDENTITY_LEN + self::GROUP_ID_LEN + self::BLOB_ID_LEN + self::FILE_SIZE_LEN + self::SYMMETRIC_KEY_LEN)) {
					throw new UnsupportedMessageTypeException('invalid group image message length');
				}
				$groupId = new GroupId(
					$piece->__invoke(self::IDENTITY_LEN),
					$piece->__invoke(self::GROUP_ID_LEN)
				);

				$blobId = $piece->__invoke(self::BLOB_ID_LEN);
				$blobLength = $piece->__invoke(self::FILE_SIZE_LEN);
				$key = $piece->__invoke(self::SYMMETRIC_KEY_LEN);

				return new Messages\GroupImageMessage
				(
					$groupId,
					bin2hex($blobId),
					current(unpack('V',$blobLength)),
					bin2hex($key)
				);

			case Messages\GroupAudioMessage::TYPE_CODE:
				/* Audio Message */
				if ($realDataLength != 1
					+ self::IDENTITY_LEN + self::GROUP_ID_LEN
					+ 2
					+ self::BLOB_ID_LEN + self::FILE_SIZE_LEN
					+ self::SYMMETRIC_KEY_LEN)  {
					throw new BadMessageException();
				}
				$groupId = new GroupId(
					$piece->__invoke(self::IDENTITY_LEN),
					$piece->__invoke(self::GROUP_ID_LEN)
				);
				$duration = $piece->__invoke(2);
				$blobId = $piece->__invoke(self::BLOB_ID_LEN);
				$blobLength = $piece->__invoke(self::FILE_SIZE_LEN);
				$key = $piece->__invoke(self::SYMMETRIC_KEY_LEN);
				return new Messages\GroupAudioMessage(
					$groupId,
					bin2hex($blobId), current(unpack('V',($blobLength))),
					bin2hex($key),
					current(unpack('s', $duration)));

			case Messages\GroupLocationMessage::TYPE_CODE:
				/* Audio Message */
				if ($realDataLength < 1
					+ self::IDENTITY_LEN + self::GROUP_ID_LEN
					+ 3)  {
					throw new BadMessageException();
				}
				$groupId = new GroupId(
					$piece->__invoke(self::IDENTITY_LEN),
					$piece->__invoke(self::GROUP_ID_LEN)
				);
				$locationStrings = explode("\n", substr($data, $pos));
				$pieces = explode(',', $locationStrings[0]);

				$latitude = doubleval($pieces[0]);
				$longitude = doubleval($pieces[1]);
				$accuracy = 0;
				if (count($pieces) >= 3)
				{
					$accuracy = doubleval($pieces[2]);
				}

				$poiName = null;
				$poiAddress = null;

				if (count($locationStrings) >= 2)
				{
					$poiName = $locationStrings[1];

					if (count($locationStrings) >= 3)
					{
						$poiAddress = str_replace('\n', "\n", $locationStrings[2]);
					}
				}

				return new Messages\GroupLocationMessage(
					$groupId,
					$latitude,
					$longitude,
					$accuracy,
					$poiName,
					$poiAddress);

			case Messages\GroupVideoMessage::TYPE_CODE:
				/* Audio Message */
				if ($realDataLength != 1
					+ self::IDENTITY_LEN + self::GROUP_ID_LEN
					// Duration
					+ 2
					// Video
					+ self::BLOB_ID_LEN + self::FILE_SIZE_LEN
					// Thumbnail
					+ self::BLOB_ID_LEN + self::FILE_SIZE_LEN
					+ self::SYMMETRIC_KEY_LEN)  {
					throw new BadMessageException();
				}
				$groupId = new GroupId(
					$piece->__invoke(self::IDENTITY_LEN),
					$piece->__invoke(self::GROUP_ID_LEN)
				);
				$duration = $piece->__invoke(2);
				$blobId = $piece->__invoke(self::BLOB_ID_LEN);
				$blobLength = $piece->__invoke(self::FILE_SIZE_LEN);
				$thumbnailBlobId = $piece->__invoke(self::BLOB_ID_LEN);
				$thumbnailBlobLength = $piece->__invoke(self::FILE_SIZE_LEN);
				$key = $piece->__invoke(self::SYMMETRIC_KEY_LEN);
				return new Messages\GroupVideoMessage(
					$groupId,
					bin2hex($blobId), current(unpack('V',($blobLength))),
					bin2hex($thumbnailBlobId), current(unpack('V',($thumbnailBlobLength))),
					bin2hex($key),
					current(unpack('s', $duration)));

			case Messages\GroupFileMessage::TYPE_CODE:
				if ($realDataLength < 1
					+ self::IDENTITY_LEN + self::GROUP_ID_LEN
					// A {} string?
					+ 2)  {
					throw new BadMessageException();
				}
				$groupId = new GroupId(
					$piece->__invoke(self::IDENTITY_LEN),
					$piece->__invoke(self::GROUP_ID_LEN)
				);

				$jsonString = substr($data, 1 + self::IDENTITY_LEN + self::GROUP_ID_LEN);

				// Verify json string first
				$decodeResult = json_decode($jsonString, true);
				if(null === $decodeResult || false === $decodeResult) {
					throw new BadMessageException();
				}

				$values = AssocArray::byAssocArray($decodeResult, ['b', 'k', 'm', 'n', 's']);
				if(null === $values) {
					throw new BadMessageException();
				}

				return new Messages\GroupFileMessage(
					$groupId,
					$values->getValue('b'),
					$values->getValue('t'),
					$values->getValue('k'),
					$values->getValue('m'),
					$values->getValue('n'),
					$values->getValue('s'),
					$values->getValue('d'));

			default:
				throw new UnsupportedMessageTypeException();
		}
	}

	/**
	 * Generate a new key pair.
	 *
	 * @return KeyPair the new key pair
	 */
	abstract public function generateKeyPair();

	/**
	 * Hashes an email address for identity lookup.
	 *
	 * @param string $email the email address
	 * @return string the email hash (hex)
	 */
	public function hashEmail($email) {
		$emailClean = strtolower(trim($email));
		return hash_hmac('sha256', $emailClean, self::EMAIL_HMAC_KEY);
	}

	/**
	 * Hashes an phone number address for identity lookup.
	 *
	 * @param string $phoneNo the phone number (in E.164 format, no leading +)
	 * @return string the phone number hash (hex)
	 */
	public function hashPhoneNo($phoneNo) {
		$phoneNoClean = preg_replace("/[^0-9]/", "", $phoneNo);
		return hash_hmac('sha256', $phoneNoClean, self::PHONENO_HMAC_KEY);
	}

	abstract protected function createRandom($size);

	/**
	 * Generate a random nonce.
	 *
	 * @return string random nonce
	 */
	public function randomNonce() {
		return $this->createRandom(Salt::box_NONCE);
	}

	/**
	 * Generate a symmetric key
	 * @return mixed
	 */
	public function symmetricKey() {
		return $this->createRandom(32);
	}

	/**
	 * Derive the public key
	 *
	 * @param string $privateKey as binary
	 * @return string as binary
	 */
	abstract public function derivePublicKey($privateKey);

	/**
	 * Check if implementation supported
	 * @return bool
	 */
	abstract public function isSupported();

	/**
	 * Validate crypt tool
	 *
	 * @return bool
	 * @throws Exception
	 */
	abstract public function validate();

	/**
	 * @param $data
	 * @return EncryptResult
	 */
	public function encryptFile($data) {
		$key = $this->symmetricKey();
		$box = $this->makeSecretBox($data, self::FILE_NONCE, $key);
		return new EncryptResult($box, $key, self::FILE_NONCE, strlen($box));
	}

	/**
	 * @param string $data as binary
	 * @param string $key as binary
	 * @return null|string
	 */
	public function decryptFile($data, $key) {
		$result =  $this->openSecretBox($data, self::FILE_NONCE, $key);
		return false === $result ? null : $result;
	}

	/**
	 * @param string $data
	 * @param string $key
	 * @return EncryptResult
	 */
	public function encryptFileThumbnail($data, $key) {
		$box = $this->makeSecretBox($data, self::FILE_THUMBNAIL_NONCE, $key);
		return new EncryptResult($box, $key,  self::FILE_THUMBNAIL_NONCE, strlen($box));
	}

	public function decryptFileThumbnail($data, $key) {
		$result = $this->openSecretBox($data, self::FILE_THUMBNAIL_NONCE, $key);
		return false === $result ? null : $result;
	}

	/**
	 * @param string $imageData
	 * @param string $privateKey as binary
	 * @param string $publicKey as binary
	 * @return EncryptResult
	 */
	public function encryptImage($imageData, $privateKey, $publicKey) {
		$nonce = $this->randomNonce();

		$box = $this->makeBox(
			$imageData,
			$nonce,
			$privateKey,
			$publicKey
		);

		return new EncryptResult($box, null, $nonce, strlen($box));
	}

	/**
	 * @param string $data as binary
	 * @param string $publicKey as binary
	 * @param string $privateKey as binary
	 * @param string $nonce as binary
	 * @return string
	 */
	public function decryptImage($data, $publicKey, $privateKey, $nonce) {
		return $this->openBox($data,
			$privateKey,
			$publicKey,
			$nonce);
	}

	/**
	 * determine random amount of PKCS7 padding
	 * @return int
	 */
	private function generatePadBytes() {
		$padbytes = 0;
		while($padbytes < 1 || $padbytes > 255) {
			$padbytes = ord($this->createRandom(1));
		}
		return $padbytes;
	}

	function __toString() {
		return 'CryptTool '.$this->getName();
	}

	/**
	 * Name of the CryptTool
	 * @return string
	 */
	abstract public function getName();

	/**
	 * Description of the CryptTool
	 * @return string
	 */
	abstract public function getDescription();
}
