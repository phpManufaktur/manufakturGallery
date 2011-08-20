<?php

/**
 * manufakturGallery
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 * 
 * FOR VERSION- AND RELEASE NOTES PLEASE LOOK AT INFO.TXT!
 */

// try to include LEPTON class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {	
	if (defined('LEPTON_VERSION')) include(WB_PATH.'/framework/class.secure.php');
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'].'/framework/class.secure.php')) {
	include($_SERVER['DOCUMENT_ROOT'].'/framework/class.secure.php'); 
} else {
	$subs = explode('/', dirname($_SERVER['SCRIPT_NAME']));	$dir = $_SERVER['DOCUMENT_ROOT'];
	$inc = false;
	foreach ($subs as $sub) {
		if (empty($sub)) continue; $dir .= '/'.$sub;
		if (file_exists($dir.'/framework/class.secure.php')) { 
			include($dir.'/framework/class.secure.php'); $inc = true;	break; 
		} 
	}
	if (!$inc) trigger_error(sprintf("[ <b>%s</b> ] Can't include LEPTON class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
}
// end include LEPTON class.secure.php
 
define('gallery_error_preset_not_exists',						'Das <b>Preset</b>: <i>%s</i> existiert nicht!');
define('gallery_error_missing_fb_id',								'Geben Sie über den Parameter <b>facebook_id</b> eine gültige Facebook ID oder einen gültigen Facebook Namen an!');
define('gallery_error_get_contents',								'Die bei Facebook angeforderten Daten konnten nicht empfangen werden.');
define('gallery_error_fb_prompt_error',							'[Facebook Fehlermeldung] %s');
define('gallery_error_no_gallery',									'Zu der ID <b>%s</b> wurde keine Fotogalerie gefunden!');
define('gallery_error_missing_album_id',						'Geben Sie über den Parameter <b>album_id</b> eine gültige Album ID an!');
define('gallery_error_request_album_id',						'<p>Beim Abruf des Album mit der ID <b>%s</b> ist ein Fehler aufgetreten:</p><p><i>%s</i></p><p>Eine mögliche Ursache für Fehler bei der Abfrage von Alben ist, dass Sie versuchen auf ein Album von einem privaten Account zuzugreifen.</p>');
define('gallery_error_no_http_request',							'<p>Es steht keine geeignete Methode zum Abfragen der Facebook Galerien zur Verfügung, bitte setzen Sie sich mit dem Support in Verbindung!</p>');

?>