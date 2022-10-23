# Threema MsgApi SDK - PHP
## Changelog

This changelog lists the most important changes for each released version. For
the full log, please refer to the git commit history.

* 1.5.6
  Supporting of group and normal location messages
* 1.4.0
  * Composer support
* 1.3.4
  * More options for E2E messages
* 1.3.3
  * URL fromString method
* 1.3.2
  * Read exif tags only on receiving image messages
  * Support file message captions
* 1.3.1
  * Fetch caption from image and file message
* 1.3.0
  * Support group text messages  (send and receive)
* 1.2.0
  * Integrate bulk lookup
  * Send video Messages
* 1.1.8
  * Fix command tests
* 1.1.7
  * Add support for "user decline" (thumbs down) delivery receipt type
* 1.1.6
  * Fix bug in PHP keystore
  * Fix SALT random generator (failed if only one bytes was requested and this happened to be '0')
* 1.1.5
  * use salt random instead mt_rand for padding (file and image messages)
* 1.1.4
  * use salt random instead mt_rand for padding (text messages)
