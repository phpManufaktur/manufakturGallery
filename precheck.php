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

// Checking Requirements

$PRECHECK['WB_VERSION'] = array('VERSION' => '2.8', 'OPERATOR' => '>=');
$PRECHECK['PHP_VERSION'] = array('VERSION' => '5.2.0', 'OPERATOR' => '>=');
$PRECHECK['WB_ADDONS'] = array(
	'dwoo' => array('VERSION' => '0.10', 'OPERATOR' => '>='),
	'droplets' => array('VERSION' => '1.0', 'OPERATOR' => '>='),
	'kit_tools' => array('VERSION' => '0.11', 'OPRATOR' => '>='),
	'droplets_extension' => array('VERSION' => '0.11', 'OPERATOR' => '>=')
);

global $database;  
$sql = "SELECT `value` FROM `".TABLE_PREFIX."settings` WHERE `name`='default_charset'";
$result = $database->query($sql);
if ($result) {
	$data = $result->fetchRow(MYSQL_ASSOC);
	$PRECHECK['CUSTOM_CHECKS'] = array(
		'Default Charset' => array(
			'REQUIRED' => 'utf-8',
			'ACTUAL' => $data['value'],
			'STATUS' => ($data['value'] === 'utf-8')
		)
	);
}

?>