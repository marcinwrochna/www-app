<?php
/**
 *	handle.php handles uploads.
 */

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__).'/..');
require_once('common.php');
require_once('utils.php');
require_once('enum.php');
include_once('log.php');
require_once('template.php');
require_once('page.php');
require_once('form.php');
require_once('user.php');
include_once('update.php'); // Apply updates.
initUser(); // initializes the $USER global.

require_once('php.php');

$uid = intval($_POST['uid']);
if ($uid != $USER['uid'] && !userCan('adminUsers'))
	die('{"error" : "Forbidden"}');

// List of valid extensions, ex. array("jpeg", "xml", "bmp")
$allowedExtensions = array('jpeg', 'jpg');
// Max file size in bytes
$sizeLimit = 5 * 1024 * 1024;
// The input name set in the javascript
$inputName = 'qqfile';
$uploader = new qqFileUploader($allowedExtensions, $sizeLimit, $inputName);
$result = $uploader->handleUpload('avatars/', 'user'. $uid .'.jpg');
$img = new Imagick('avatars/user'. $uid .'.jpg');
$maxWidth = 200; $maxHeight = 230;
if ($img->getImageWidth() > $maxWidth || $img->getImageHeight() > $maxHeight)
	$img->cropThumbnailImage($maxWidth, $maxHeight);
$img->writeImage();

header('Content-Type: text/plain');
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
