<?php
/**
 * manufakturGallery
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */

// prevent this file from being accessed directly
if (!defined('WB_PATH')) die('invalid call of '.$_SERVER['SCRIPT_NAME']);

 
define('gallery_error_preset_not_exists',						'Das <b>Preset</b>: <i>%s</i> existiert nicht!');
define('gallery_error_missing_fb_id',								'Geben Sie über den Parameter <b>facebook_id</b> eine gültige Facebook ID oder einen gültigen Facebook Namen an!');
define('gallery_error_get_contents',								'Die bei Facebook angeforderten Daten konnten nicht empfangen werden.');
define('gallery_error_fb_prompt_error',							'[Facebook Fehlermeldung] %s');
define('gallery_error_no_gallery',									'Zu der ID <b>%s</b> wurde keine Fotogalerie gefunden!');
define('gallery_error_missing_album_id',						'Geben Sie über den Parameter <b>album_id</b> eine gültige Album ID an!');
define('gallery_error_request_album_id',						'<p>Beim Abruf des Album mit der ID <b>%s</b> ist ein Fehler aufgetreten:</p><p><i>%s</i></p><p>Eine mögliche Ursache für Fehler bei der Abfrage von Alben ist, dass Sie versuchen auf ein Album von einem privaten Account zuzugreifen.</p>');

?>