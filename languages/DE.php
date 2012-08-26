<?php

/**
 * manufakturGallery
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011 - 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
  if (defined('LEPTON_VERSION'))
    include(WB_PATH.'/framework/class.secure.php');
}
else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root.'/framework/class.secure.php')) {
    include($root.'/framework/class.secure.php');
  }
  else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

define('gallery_error_preset_not_exists',						'Das <b>Preset</b>: <i>%s</i> existiert nicht!');
define('gallery_error_missing_fb_id',								'Geben Sie über den Parameter <b>facebook_id</b> eine gültige Facebook ID oder einen gültigen Facebook Namen an!');
define('gallery_error_get_contents',								'Die bei Facebook angeforderten Daten konnten nicht empfangen werden.');
define('gallery_error_fb_prompt_error',							'[Facebook Fehlermeldung] %s');
define('gallery_error_no_gallery',									'Zu der ID <b>%s</b> wurde keine Fotogalerie gefunden!');
define('gallery_error_missing_album_id',						'Geben Sie über den Parameter <b>album_id</b> eine gültige Album ID an!');
define('gallery_error_request_album_id',						'<p>Beim Abruf des Album mit der ID <b>%s</b> ist ein Fehler aufgetreten:</p><p><i>%s</i></p><p>Eine mögliche Ursache für Fehler bei der Abfrage von Alben ist, dass Sie versuchen auf ein Album von einem privaten Account zuzugreifen.</p>');
define('gallery_error_no_http_request',							'<p>Es steht keine geeignete Methode zum Abfragen der Facebook Galerien zur Verfügung, bitte setzen Sie sich mit dem Support in Verbindung!</p>');

?>