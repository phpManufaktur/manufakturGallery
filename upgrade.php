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

require_once(WB_PATH.'/modules/kit_tools/class.droplets.php');

global $admin;

$error = '';

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
	global $admin;
	$admin->print_error($error);
}

?>