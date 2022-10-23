<?php
/**
 * @author Silvan Engeler <silvan.engeler@threema.ch>
 * @copyright Copyright (c) 2020 Threema GmbH
 * @link https://gateway.threema.ch/en/developer
 */

namespace Threema\MsgApi\Helpers;

use Closure;
use Threema\MsgApi\Commands\Results\CapabilityResult;
use Threema\MsgApi\Commands\Results\DownloadFileResult;
use Threema\MsgApi\Commands\Results\SendE2EResult;
use Threema\MsgApi\Commands\Results\UploadFileResult;
use Threema\MsgApi\Connection;
use Threema\MsgApi\Exceptions\BadMessageException;
use Threema\MsgApi\Exceptions\DecryptionFailedException;
use Threema\MsgApi\Exceptions\UnsupportedMessageTypeException;
use Threema\MsgApi\Messages\AudioMessage;
use Threema\MsgApi\Messages\FileMessage;
use Threema\MsgApi\Messages\GroupCreateMessage;
use Threema\MsgApi\Messages\GroupDeletePhoto;
use Threema\MsgApi\Messages\GroupRenameMessage;
use Threema\MsgApi\Messages\GroupSetPhoto;
use Threema\MsgApi\Messages\ImageMessage;
use Threema\MsgApi\Messages\ThreemaMessage;
use Threema\MsgApi\Messages\VideoMessage;
use Threema\MsgApi\Tools\CryptTool;
use Threema\MsgApi\Core\Exception;
use Threema\MsgApi\Tools\EncryptResult;
use Threema\MsgApi\Tools\FileAnalysisResult;
use Threema\MsgApi\Tools\FileAnalysisTool;
use Threema\MsgApi\Types\GroupId;

class E2EHelper {
	/**
	 * @var Connection
	 */
	private $connection;

	/**
	 * @var CryptTool
	 */
	private $cryptTool;

	/**
	 * @var string (bin)
	 */
	private $privateKey;
	/**
	 * @var null|FileAnalysisTool
	 */
	private $fileAnalysisTool;

	/**
	 * @param string $privateKey (binary)
	 * @param Connection $connection
	 * @param CryptTool|null $cryptTool
	 * @param FileAnalysisTool|null $fileAnalysisTool
	 */
	public function __construct(
		$privateKey,
		Connection $connection,
		CryptTool $cryptTool = null,
		FileAnalysisTool $fileAnalysisTool = null) {
		$this->connection = $connection;
		$this->cryptTool = $cryptTool;
		$this->privateKey = $privateKey;
		$this->fileAnalysisTool = $fileAnalysisTool;

		if(null === $this->cryptTool) {
			$this->cryptTool = CryptTool::getInstance();
		}

		if(null === $this->fileAnalysisTool) {
			$this->fileAnalysisTool = new FileAnalysisTool();
		}
	}

	public function sendGroupCreate(string $threemaId,
	                                string $groupId,
	                                array $members,
	                                ?array $moreOptions = null): SendE2EResult
	{
		$options = $moreOptions;
		if (null === $options)
		{
			$options = [];
		}

		$options['group'] = 1;
		$options['noPush'] = 1;

		$nonce = $this->getCryptTool()->randomNonce();
		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, null);

		$box = $this->getCryptTool()->padAndMakeBox(
			GroupCreateMessage::TYPE_CODE,
			$groupId . implode('', $members),
			$nonce,
			$this->getPrivateKey(),
			$receiverPublicKey
		);

		return $this->sendE2E(
			$threemaId,
			$nonce,
			$box,
			$options
		);
	}


	public function sendGroupPhoto(string $threemaId,
	                                string $groupId,
	                                string $groupPhotoInJpgAsBinary,
	                                ?array $moreOptions = null): SendE2EResult
	{
		$options = $moreOptions;
		if (null === $options)
		{
			$options = [];
		}

		$options['group'] = 1;
		$options['noPush'] = 1;

		$nonce = $this->getCryptTool()->randomNonce();

		$res = $this->getCryptTool()->encryptFile($groupPhotoInJpgAsBinary);
		$uploadRes = $this->getConnection()->uploadFile($res->getData());

		$message = $groupId;
		$message .= hex2bin($uploadRes->getBlobId());
		$message .= pack('V', $res->getSize());
		$message .= $res->getKey();

		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, null);

//		$uploadResult =  $this->connection->uploadFile($encryptionResult->getData());
//
//		if (null === $uploadResult) {
//			throw new Exception('could not upload the file (no upload result)');
//		}
//
//		if(!$uploadResult->isSuccess()) {
//			throw new Exception('could not upload the file ('.$uploadResult->getErrorCode().' '.$uploadResult->getErrorMessage().') '.$uploadResult->getRawResponse());
//		}

		$box = $this->getCryptTool()->padAndMakeBox(
			GroupSetPhoto::TYPE_CODE,
			$message,
			$nonce,
			$this->getPrivateKey(),
			$receiverPublicKey
		);

		return $this->sendE2E(
			$threemaId,
			$nonce,
			$box,
			$options
		);
	}

	/**
	 * @param string $threemaId
	 * @param string $groupId
	 * @return SendE2EResult
	 * @throws Exception
	 */
	public function deleteGroupPhoto(string $threemaId, string $groupId): SendE2EResult
	{
		// get nonce
		$nonce = $this->getCryptTool()->randomNonce();

		// build message
		$message = $groupId;

		// fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, null);

		// build box
		$box = $this->getCryptTool()->padAndMakeBox(
			GroupDeletePhoto::TYPE_CODE,
			$message,
			$nonce,
			$this->getPrivateKey(),
			$receiverPublicKey
		);

		// send message
		return $this->sendE2E(
			$threemaId,
			$nonce,
			$box
		);
	}

	public function sendGroupRename(string $threemaId,
	                                string $groupId,
	                                string $groupName,
	                                ?array $moreOptions = null): SendE2EResult
	{
		$options = $moreOptions;
		if (null === $options)
		{
			$options = [];
		}

		$options['group'] = 1;
		$options['noPush'] = 1;

		$nonce = $this->getCryptTool()->randomNonce();
		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, null);

		$box = $this->getCryptTool()->padAndMakeBox(
			GroupRenameMessage::TYPE_CODE,
			$groupId.$groupName,
			$nonce,
			$this->getPrivateKey(),
			$receiverPublicKey
		);

		return $this->sendE2E(
			$threemaId,
			$nonce,
			$box,
			$options
		);
	}


	/**
	 * @return CryptTool
	 */
	protected function getCryptTool(): CryptTool {
		return $this->cryptTool;
	}

	/**
	 * @return Connection
	 */
	protected function getConnection(): Connection {
		return $this->connection;
	}

	/**
	 * @return null|FileAnalysisTool
	 */
	protected function getFileAnalysisTool(): ?FileAnalysisTool {
		return $this->fileAnalysisTool;
	}

	/**
	 * @return string
	 */
	public function getPrivateKey() {
		return $this->privateKey;
	}

	/**
	 * Crypt a text message and send it to the threemaId
	 *
	 * @param string $threemaId
	 * @param string $text
	 *
	 * @return SendE2EResult
	 * @throws Exception
	 */
	public function sendTextMessage(string $threemaId, string $text): SendE2EResult {
		//random nonce first
		$nonce = $this->cryptTool->randomNonce();

		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, null);

		//create a box
		$textMessage = $this->cryptTool->encryptMessageText(
			$text,
			$this->privateKey,
			$receiverPublicKey,
			$nonce);

		return $this->sendE2E($threemaId, $nonce, $textMessage);
	}

	/**
	 * Crypt a location message and send it to the threemaId
	 *
	 * @param string $threemaId
	 * @param string $text
	 *
	 * @return SendE2EResult
	 * @throws Exception
	 */
	public function sendLocationMessage(string $threemaId,
	                                    float $lat, float $lng,
	                                    ?float $accuracy = null, ?string $poiName = null, ?string $poiAddress = null): SendE2EResult {
		//random nonce first
		$nonce = $this->cryptTool->randomNonce();

		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, null);

		//create a box
		$textMessage = $this->cryptTool->encryptMessageLocation(
			$lat,
			$lng,
			$accuracy,
			$poiName,
			$poiAddress,
			$this->privateKey,
			$receiverPublicKey,
			$nonce);

		return $this->sendE2E($threemaId, $nonce, $textMessage);
	}

	/**
	 * Crypt a group text message and send it to the threemaId
	 *
	 * @param GroupId $groupId
	 * @param string $threemaId
	 * @param string $text
	 * @param array|null $moreOptions
	 *
	 * @return SendE2EResult
	 * @throws Exception
	 */
	public function sendGroupTextMessage(GroupId $groupId,
	                                           string $threemaId,
	                                           string $text,
	                                           ?array $moreOptions = null): SendE2EResult {
		$options = $moreOptions;
		if (null === $options)
		{
			$options = [];
		}

		$options['group'] = 1;

		//random nonce first
		$nonce = $this->cryptTool->randomNonce();

		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, null);

		//create a box
		$textMessage = $this->cryptTool->encryptGroupMessageText(
			$groupId,
			$text,
			$this->privateKey,
			$receiverPublicKey,
			$nonce);

		return $this->sendE2E($threemaId, $nonce, $textMessage, $options);
	}


	/**
	 * Crypt a group location message and send it to the threemaId
	 *
	 * @param GroupId $groupId
	 * @param string $threemaId
	 * @param float $lat
	 * @param float $lng
	 * @param float|null $accuracy
	 * @param string|null $poiName
	 * @param string|null $poiAddress
	 * @param array|null $moreOptions
	 * @return SendE2EResult
	 * @throws Exception
	 */
	public function sendGroupLocationMessage(GroupId $groupId,
											string $threemaId,
											float $lat, float $lng,
											?float $accuracy = null, ?string $poiName = null, ?string $poiAddress = null,
											 ?array $moreOptions = null): SendE2EResult {
		$options = $moreOptions;
		if (null === $options)
		{
			$options = [];
		}

		$options['group'] = 1;

		//random nonce first
		$nonce = $this->cryptTool->randomNonce();

		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, null);

		//create a box
		$textMessage = $this->cryptTool->encryptGroupMessageLocation(
			$groupId,
			$lat,
			$lng,
			$accuracy,
			$poiName,
			$poiAddress,
			$this->privateKey,
			$receiverPublicKey,
			$nonce);

		return $this->sendE2E($threemaId, $nonce, $textMessage);
	}

	/**
	 * Crypt a file (and thumbnail if given), upload the blob and send it to the given threemaId
	 *
	 * @param GroupId $groupId
	 * @param string $threemaId
	 * @param string $filePath
	 * @param null|string $thumbnailPath
	 * @param null|string $caption
	 * @param array|null $moreOptions
	 * @param string|null $fileName
	 * @param int $renderingType
	 *
	 * @throws Exception
	 * @return SendE2EResult
	 */
	public function sendGroupFileMessage(
		GroupId $groupId,
		string $threemaId,
		string $filePath,
		?string $thumbnailPath = null,
		?string $caption = null,
		?array $moreOptions = null,
		?string $fileName = null,
		?int $renderingType = null
	): SendE2EResult {
		$options = $moreOptions;
		if (null === $options) {
			$options = [];
		}

		$options['group'] = 1;

		$nonce = $this->cryptTool->randomNonce();
		$fileMessage = $this->processFileSending($threemaId, $filePath, new class($groupId, $nonce, $fileName, $renderingType) implements ProcessFileSend {
			/** @var string */
			private $nonce;

			/** @var GroupId */
			private $groupId;

			/** @var string|null */
			private $fileName;

			/** @var int|null */
			private $renderingType;

			/**
			 *  constructor.
			 * @param GroupId $groupId
			 * @param string $nonce
			 * @param string|null $fileName
			 * @param int|null $renderingType
			 */
			public function __construct(
				GroupId $groupId,
				string $nonce,
				?string $fileName = null,
				?int $renderingType = null
			) {
				$this->nonce = $nonce;
				$this->groupId = $groupId;
				$this->fileName = $fileName;
				$this->renderingType = $renderingType;
			}

			function process(
				CryptTool $cryptTool,
				string $privateKey,
				UploadFileResult $uploadResult,
				EncryptResult $encryptionResult,
				FileAnalysisResult $fileAnalyzeResult,
				string $receiverPublicKey,
				?string $caption,
				?UploadFileResult $thumbnailUploadResult
			): string {
				return $cryptTool->encryptGroupMessageFile(
					$this->groupId,
					$uploadResult,
					$encryptionResult,
					$fileAnalyzeResult,
					$privateKey,
					$receiverPublicKey,
					$this->nonce,
					$thumbnailUploadResult,
					$caption,
					$this->fileName,
					$this->renderingType
				);
			}

		}, $caption, $thumbnailPath);

		return $this->sendE2E($threemaId, $nonce, $fileMessage, $options);
	}

	protected function sendE2E(string $threemaId,
	                           string $nonce,
	                           string $textMessage,
	                           ?array $moreOptions = null): SendE2EResult {
		if (null === $moreOptions) {
			$moreOptions = [];
		}
		return $this->connection->sendE2E($threemaId, $nonce, $textMessage, $moreOptions);
	}

	/**
	 * Crypt a image file, upload the blob and send the image message to the threemaId
	 *
	 * @param string $threemaId
	 * @param string $imagePath
	 *
	 * @return SendE2EResult
	 * @throws Exception
	 */
	public function sendImageMessage(string $threemaId, string $imagePath): SendE2EResult {
		//analyse the file
		$fileAnalyzeResult = $this->fileAnalysisTool->analyse($imagePath);

		if(null === $fileAnalyzeResult) {
			throw new Exception('could not analyze the file');
		}

		if (true !== $this->fileAnalysisTool->isImage($imagePath)) {
			throw new Exception('file is not a jpg or png');
		}

		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, function(CapabilityResult $capabilityResult) {
			return true === $capabilityResult->canImage();
		});

		//encrypt the image file
		$encryptionResult = $this->cryptTool->encryptImage(
				$this->fileAnalysisTool->getContent($imagePath),
				$this->privateKey,
				$receiverPublicKey);

		$uploadResult =  $this->connection->uploadFile($encryptionResult->getData());

		if($uploadResult == null || !$uploadResult->isSuccess()) {
			throw new Exception('could not upload the image ('.$uploadResult->getErrorCode().' '.$uploadResult->getErrorMessage().') '.$uploadResult->getRawResponse());
		}

		$nonce = $this->cryptTool->randomNonce();

		//create a image message box
		$imageMessage = $this->cryptTool->encryptImageMessage(
			$uploadResult,
			$encryptionResult,
			$this->privateKey,
			$receiverPublicKey,
			$nonce);

		return $this->sendE2E($threemaId, $nonce, $imageMessage);
	}

	/**
	 * Crypt a video file (and thumbnail if given), upload the blob and send it to the given threemaId
	 *
	 * @param string $threemaId
	 * @param int $duration
	 * @param string $videoPath
	 * @param string $videoThumbnailPath
	 *
	 * @return SendE2EResult
	 * @throws Exception
	 */
	public function sendVideoMessage(string $threemaId,
	                                       int $duration,
	                                       string $videoPath,
	                                       ?string $videoThumbnailPath = null): SendE2EResult {
		//analyse the files
		$fileAnalyzeResult =  $this->fileAnalysisTool->analyse($videoPath);

		if(null === $fileAnalyzeResult) {
			throw new Exception('could not analyze the video file');
		}

		//analyse the files
		if (true !== $this->fileAnalysisTool->isImage($videoThumbnailPath)) {
			throw new Exception('thumbnail is not a jpg or png');
		}

		if (false === is_numeric($duration))
		{
			throw new Exception('duration must be a integer');
		}

		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, function(CapabilityResult $capabilityResult) {
			return true === $capabilityResult->canVideo() ;
		});

		//encrypt the main file
		$encryptionResult = $this->cryptTool->encryptFile(
			$this->fileAnalysisTool->getContent($videoPath)
		);
		$uploadResult =  $this->connection->uploadFile($encryptionResult->getData());

		if (null === $uploadResult) {
			throw new Exception('upload failed (no upload result)');
		}

		if(!$uploadResult->isSuccess()) {
			throw new Exception('could not upload the file ('.$uploadResult->getErrorCode().' '.$uploadResult->getErrorMessage().') '.$uploadResult->getRawResponse());
		}

		$thumbnailUploadResult = null;

		//encrypt the thumbnail file
		$thumbnailEncryptionResult = $this->cryptTool->encryptFileThumbnail(
			$this->fileAnalysisTool->getContent($videoThumbnailPath),
			$encryptionResult->getKey());

		$thumbnailUploadResult = $this->connection->uploadFile(
			$thumbnailEncryptionResult->getData()
		);

		if(null === $thumbnailUploadResult) {
			throw new Exception('upload failed (no result)');
		}

		if(!$thumbnailUploadResult->isSuccess()) {
			throw new Exception('could not upload the thumbnail file ('.$thumbnailUploadResult->getErrorCode().' '.$thumbnailUploadResult->getErrorMessage().') '.$thumbnailUploadResult->getRawResponse());
		}

		$nonce = $this->cryptTool->randomNonce();

		//create a file message box
		$fileMessage = $this->cryptTool->encryptVideoMessage(
			$duration,
			$uploadResult,
			$thumbnailUploadResult,
			$encryptionResult,
			$thumbnailEncryptionResult,
			$this->privateKey,
			$receiverPublicKey,
			$nonce);

		return $this->sendE2E($threemaId, $nonce, $fileMessage);
	}

	/**
	 * Crypt a audio file, upload the blob and send it to the given threemaId
	 *
	 * @param string $threemaId
	 * @param int $duration
	 * @param string $audioPath
	 *
	 * @return SendE2EResult
	 * @throws Exception
	 */
	public function sendAudioMessage(string $threemaId,
	                                       int $duration,
	                                       string $audioPath): SendE2EResult {
		//analyse the files
		$fileAnalyzeResult =  $this->fileAnalysisTool->analyse($audioPath);

		if(null === $fileAnalyzeResult) {
			throw new Exception('could not analyze the audio file');
		}

		if (false === is_numeric($duration)) {
			throw new Exception('duration must be a integer');
		}

		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, function(CapabilityResult $capabilityResult) {
			return true === $capabilityResult->canAudio();
		});

		//encrypt the main file
		$encryptionResult = $this->cryptTool->encryptFile(
			$this->fileAnalysisTool->getContent($audioPath)
		);
		$uploadResult =  $this->connection->uploadFile($encryptionResult->getData());

		if($uploadResult == null || !$uploadResult->isSuccess()) {
			throw new Exception('could not upload the file ('.$uploadResult->getErrorCode().' '.$uploadResult->getErrorMessage().') '.$uploadResult->getRawResponse());
		}

		$nonce = $this->cryptTool->randomNonce();

		//create a file message box
		$fileMessage = $this->cryptTool->encryptAudioMessage(
			$duration,
			$uploadResult,
			$encryptionResult,
			$this->privateKey,
			$receiverPublicKey,
			$nonce);

		return $this->sendE2E($threemaId, $nonce, $fileMessage);
	}

	/**
	 * @param string $threemaId
	 * @param string $filePath
	 * @param ProcessFileSend $encryptFileMessageCallback
	 * @param string|null $caption
	 * @param string|null $thumbnailPath
	 * @return string
	 * @throws Exception
	 */
	private function processFileSending(string $threemaId,
	                                   string $filePath,
	                                   ProcessFileSend $encryptFileMessageCallback,
	                                   ?string $caption = null,
	                                   ?string $thumbnailPath = null): string {
		//analyse the file
		$fileAnalyzeResult = $this->fileAnalysisTool->analyse($filePath);

		if(null === $fileAnalyzeResult) {
			throw new Exception('could not analyze the file');
		}

		if (strlen($thumbnailPath) > 0) {
			if (true !== $this->fileAnalysisTool->isImage($thumbnailPath)) {
				throw new Exception('thumbnail is not a jpg or png'.$thumbnailPath);
			}
		}

		//fetch the public key
		$receiverPublicKey = $this->fetchPublicKeyAndCheckCapability($threemaId, function(CapabilityResult $capabilityResult) {
			return true === $capabilityResult->canFile();
		});

		//encrypt the main file
		$encryptionResult = $this->cryptTool->encryptFile(
			$this->fileAnalysisTool->getContent($filePath)
		);

		$uploadResult =  $this->connection->uploadFile($encryptionResult->getData());

		if (null === $uploadResult) {
			throw new Exception('could not upload the file (no upload result)');
		}

		if(!$uploadResult->isSuccess()) {
			throw new Exception('could not upload the file ('.$uploadResult->getErrorCode().' '.$uploadResult->getErrorMessage().') '.$uploadResult->getRawResponse());
		}

		$thumbnailUploadResult = null;

		//encrypt the thumbnail file (if exists)
		if(strlen($thumbnailPath) > 0) {
			//encrypt the main file
			$thumbnailEncryptionResult = $this->cryptTool->encryptFileThumbnail(
				$this->fileAnalysisTool->getContent($thumbnailPath),
				$encryptionResult->getKey());
			$thumbnailUploadResult = $this->connection->uploadFile(
				$thumbnailEncryptionResult->getData());

			if($thumbnailUploadResult == null || !$thumbnailUploadResult->isSuccess()) {
				throw new Exception('could not upload the thumbnail file ('.$thumbnailUploadResult->getErrorCode().' '.$thumbnailUploadResult->getErrorMessage().') '.$thumbnailUploadResult->getRawResponse());
			}
		}

		return $encryptFileMessageCallback->process(
			$this->cryptTool,
			$this->privateKey,
			$uploadResult,
			$encryptionResult,
			$fileAnalyzeResult,
			$receiverPublicKey,
			$caption,
			$thumbnailUploadResult);
	}


	/**
	 * Crypt a file (and thumbnail if given), upload the blob and send it to the given threemaId
	 *
	 * @param string $threemaId
	 * @param string $filePath
	 * @param null|string $thumbnailPath
	 * @param null|string $caption
	 * @param string|null $fileName
	 * @param int $renderingType
	 *
	 * @throws Exception
	 * @return SendE2EResult
	 */
	public function sendFileMessage(
		string $threemaId,
	  	string $filePath,
	  	?string $thumbnailPath = null,
	  	?string $caption = null,
		?string $fileName = null,
		?int $renderingType = null
	): SendE2EResult {
		$nonce = $this->cryptTool->randomNonce();
		$fileMessage = $this->processFileSending($threemaId, $filePath, new class($nonce, $fileName, $renderingType) implements ProcessFileSend {
			/** @var string */
			private $nonce;

			/** @var string|null */
			private $fileName;

			/** @var int|null */
			private $renderingType;

			/**
			 *  constructor.
			 * @param string $nonce
			 * @param string|null $fileName
			 * @param int|null $renderingType
			 */
			public function __construct(
				string $nonce,
				?string $fileName = null,
				?int $renderingType = null
			) {
				$this->nonce = $nonce;
				$this->fileName = $fileName;
				$this->renderingType = $renderingType;
			}

			function process(
				CryptTool $cryptTool,
				string $privateKey,
				UploadFileResult $uploadResult,
				EncryptResult $encryptionResult,
				FileAnalysisResult $fileAnalyzeResult,
				string $receiverPublicKey,
				?string $caption,
				?UploadFileResult $thumbnailUploadResult
			): string {
				return $cryptTool->encryptFileMessage(
					$uploadResult,
					$encryptionResult,
					$fileAnalyzeResult,
					$privateKey,
					$receiverPublicKey,
					$this->nonce,
					$thumbnailUploadResult,
					$caption,
					$this->fileName,
					$this->renderingType
				);
			}

		}, $caption, $thumbnailPath);

		return $this->sendE2E($threemaId, $nonce, $fileMessage);
	}

	/**
	 * Encrypt a message and download the files of the message to the $outputFolder
	 *
	 * @param string $threemaId
	 * @param string $messageId
	 * @param string $box box as binary string
	 * @param string $nonce nonce as binary string
	 * @param string|null $outputFolder folder for storing the files
	 * @param Closure $downloadMessage
	 * @return ReceiveMessageResult
	 * @throws Exception
	 * @throws BadMessageException
	 * @throws DecryptionFailedException
	 * @throws UnsupportedMessageTypeException
	 */
	public function receiveMessage(string $threemaId,
										 string $messageId,
										 string $box,
										 string $nonce,
										 ?string $outputFolder = null,
										 ?Closure $downloadMessage = null): ReceiveMessageResult {

		if($outputFolder == null || strlen($outputFolder) == 0) {
			$outputFolder = '.';
		}

		//fetch the public key
		$receiverPublicKey = $this->connection->fetchPublicKey($threemaId);

		if(null === $receiverPublicKey || !$receiverPublicKey->isSuccess()) {
			throw new Exception('Invalid threema id');
		}

		$message = $this->cryptTool->decryptMessage(
			$box,
			$this->privateKey,
			hex2bin($receiverPublicKey->getPublicKey()),
			$nonce
		);

		if(null === $message || false === is_object($message)) {
			throw new Exception('Could not encrypt box');
		}

		$receiveResult = new ReceiveMessageResult($messageId, $message);

		if($message instanceof ImageMessage) {
			$result = $this->downloadFile($message, $message->getBlobId(), $downloadMessage);
			if(null !== $result && true === $result->isSuccess()) {
				$image = $this->cryptTool->decryptImage(
					$result->getData(),
					hex2bin($receiverPublicKey->getPublicKey()),
					$this->privateKey,
					$message->getNonce()
				);

				if (null === $image) {
					throw new Exception('decryption of image failed');
				}
				//save file
				$filePath = $outputFolder . '/' . $messageId . '.jpg';
				$f = fopen($filePath, 'w+');
				fwrite($f, $image);
				fclose($f);

				$receiveResult->addFile('image', $filePath);

				// Extract caption
				$result = $this->getFileAnalysisTool()->analyse($filePath);
				if (null !== $result && is_array($result->getExifTags())) {
					$exifTags = $result->getExifTags();
					foreach (['UserComment', 'Artist'] as $exifTag) {
						if (true === array_key_exists($exifTag, $exifTags)
							&& strlen($exifTags[$exifTag])) {
							$message->setCaption($exifTags[$exifTag]);
							break;
						}
					}
				}
			}
		}
		else if($message instanceof FileMessage) {
			$result = $this->downloadFile($message, $message->getBlobId(), $downloadMessage);

			if(null !== $result && true === $result->isSuccess()) {
				$file = $this->cryptTool->decryptFile(
					$result->getData(),
					hex2bin($message->getEncryptionKey()));

				if (null === $file) {
					throw new Exception('file decryption failed');
				}

				//save file
				$filePath = $outputFolder . '/' . $messageId . '-' . $message->getFilename();
				file_put_contents($filePath, $file);

				$receiveResult->addFile('file', $filePath);
			}

			if(null !== $message->getThumbnailBlobId() && strlen($message->getThumbnailBlobId()) > 0) {
				$result = $this->downloadFile($message, $message->getThumbnailBlobId(), $downloadMessage);
				if(null !== $result && true === $result->isSuccess()) {
					$file = $this->cryptTool->decryptFileThumbnail(
						$result->getData(),
						hex2bin($message->getEncryptionKey()));

					if(null === $file) {
						throw new Exception('thumbnail decryption failed');
					}
					//save file
					$filePath = $outputFolder.'/'.$messageId.'-thumbnail-'.$message->getFilename();
					file_put_contents($filePath, $file);

					$receiveResult->addFile('thumbnail', $filePath);
				}
			}
		}
		else if($message instanceof VideoMessage) {
			$result = $this->downloadFile($message, $message->getBlobId(), $downloadMessage);

			if(null !== $result && true === $result->isSuccess()) {
				$file = $this->cryptTool->decryptFile(
					$result->getData(),
					hex2bin($message->getEncryptionKey()));

				if (null === $file) {
					throw new Exception('video file decryption failed');
				}

				//save file
				$filePath = $outputFolder . '/' . $messageId . '.mpg';
				file_put_contents($filePath, $file);

				$receiveResult->addFile('videoFile', $filePath);
			}

			if(null !== $message->getThumbnailBlobId() && strlen($message->getThumbnailBlobId()) > 0) {
				$result = $this->downloadFile($message, $message->getThumbnailBlobId(), $downloadMessage);
				if(null !== $result && true === $result->isSuccess()) {
					$file = $this->cryptTool->decryptFileThumbnail(
						$result->getData(),
						hex2bin($message->getEncryptionKey()));

					if(null === $file) {
						throw new Exception('video thumbnail decryption failed');
					}
					//save file
					$filePath = $outputFolder.'/'.$messageId.'-thumbnail.jpg';
					file_put_contents($filePath, $file);

					$receiveResult->addFile('videoThumbnail', $filePath);
				}
			}
		}
		else if($message instanceof AudioMessage) {
			$result = $this->downloadFile($message, $message->getBlobId(), $downloadMessage);

			if(null !== $result && true === $result->isSuccess()) {
				$file = $this->cryptTool->decryptFile(
					$result->getData(),
					hex2bin($message->getEncryptionKey()));

				if (null === $file) {
					throw new Exception('audio file decryption failed');
				}

				//save file
				$filePath = $outputFolder . '/' . $messageId . '.ogg';
				file_put_contents($filePath, $file);

				$receiveResult->addFile('audioFile', $filePath);
			}
		}

		return $receiveResult;
	}

	/**
	 * Fetch a public key and check the capability of the threemaId
	 *
	 * @param string $threemaId
	 * @param Closure $capabilityCheck
	 * @return string Public key as binary
	 * @throws Exception
	 */
	protected function fetchPublicKeyAndCheckCapability(string $threemaId, ?Closure $capabilityCheck = null): string {
		//fetch the public key
		$receiverPublicKey = $this->connection->fetchPublicKey($threemaId);

		if(null === $receiverPublicKey || !$receiverPublicKey->isSuccess()) {
			throw new Exception('Invalid threema id');
		}

		if(null !== $capabilityCheck) {
			//check capability
			$capability = $this->connection->keyCapability($threemaId);
			if(null === $capability || false === $capabilityCheck->__invoke($capability)) {
				throw new Exception('threema id does not have the capability');
			}
		}

		return hex2bin($receiverPublicKey->getPublicKey());
	}

	/**
	 * @param ThreemaMessage $message
	 * @param string $blobId blob id as hex
	 * @param Closure|null $downloadMessage
	 * @return null|DownloadFileResult
	 * @throws Exception
	 */
	private function downloadFile(ThreemaMessage $message, string $blobId, ?Closure $downloadMessage = null): ?DownloadFileResult {
		if(null === $downloadMessage
			|| true === $downloadMessage->__invoke($message, $blobId)) {
			//make a download
			$result = $this->connection->downloadFile($blobId);
			if(null === $result || false === $result->isSuccess()) {
				throw new Exception('could not download the file with blob id '.$blobId);
			}

			return $result;
		}
		return null;
	}
}

interface ProcessFileSend
{
	function process(CryptTool $cryptTool,
	                 string $privateKey,
	                 UploadFileResult $uploadResult,
	                 EncryptResult $encryptResult,
	                 FileAnalysisResult $fileAnalyzeResult,
	                 string $publicKey,
	                 ?string $caption,
	                 ?UploadFileResult $thumbnailUploadResult): string;
}
