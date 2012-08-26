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

require_once(WB_PATH.'/modules/kit_tools/class.droplets.php');

global $admin;
global $database;

$error = '';

// Release 0.17 - changed frontend.css to manufaktur_gallery.css
$SQL = "UPDATE `".TABLE_PREFIX."mod_droplets_extension` SET `drop_file`='manufaktur_gallery.css' ".
  "WHERE `drop_module_dir`='manufaktur_gallery' AND `drop_type`='css'";
if (!$database->query($SQL)) {
  $admin->print_error('[UPDATE mod_droplets_extension] '.$database->get_error());
}

// remove Droplets
$dbDroplets = new dbDroplets();
$where = array(dbDroplets::field_name => 'kit_dirlist');
if (!$dbDroplets->sqlDeleteRecord($where)) {
	$message = sprintf('[UPGRADE] Error uninstalling Droplet: %s', $dbDroplets->getError());
}

// Install Droplets
$droplets = new checkDroplets();
$droplets->droplet_path = WB_PATH.'/modules/manufaktur_gallery/droplets/';

if ($droplets->insertDropletsIntoTable()) {
  $message = 'The Droplets for manufakturGallery where successfully installed! Please look at the Help for further informations.';
}
else {
  $message = 'The installation of the Droplets for manufakturGallery failed. Error: '. $droplets->getError();
}
if ($message != "") {
  echo '<script language="javascript">alert ("'.$message.'");</script>';
}

// Prompt Errors
if (!empty($error)) {
	$admin->print_error($error);
}

?>