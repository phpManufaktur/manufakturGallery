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

require_once(WB_PATH.'/modules/dbconnect_le/include.php');
require_once(WB_PATH.'/modules/dwoo/include.php');
require_once(WB_PATH.'/modules/droplets_extension/class.pages.php');

// include language file for the gallery
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); // Vorgabe: DE verwenden 
	if (!defined('GALLERY_LANGUAGE')) define('GALLERY_LANGUAGE', 'DE'); // die Konstante gibt an in welcher Sprache manufakturGallery aktuell arbeitet
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php');
	if (!defined('GALLERY_LANGUAGE')) define('GALLERY_LANGUAGE', LANGUAGE); 
}

function getAlbumID($page_id, &$params=array()) {
	$db_wysiwyg = new db_wb_mod_wysiwyg();
	$SQL = sprintf(	"SELECT %s FROM %s WHERE %s='%s'",
									db_wb_mod_wysiwyg::field_text,
									$db_wysiwyg->getTableName(),
									db_wb_mod_wysiwyg::field_page_id,
									$page_id);
	$sections = array();
	if (!$db_wysiwyg->sqlExec($SQL, $sections)) {
		trigger_error(sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $db_wysiwyg->getError()), E_USER_ERROR);
		return false;
	}
	$album_id = '';
	
	foreach ($sections as $section) { 
		if (false !== ($start = strpos($section[db_wb_mod_wysiwyg::field_text], '[[manufaktur_gallery?'))) {
			$start = $start+strlen('[[manufaktur_gallery?');
			$end = strpos($section[db_wb_mod_wysiwyg::field_text], ']]', $start);
			$param_str = substr($section[db_wb_mod_wysiwyg::field_text], $start, $end-$start);
			$param_str = str_ireplace('&amp;', '&', $param_str);
			parse_str($param_str, $params);
			if (isset($params['album_id'])) {
				$album_id = $params['album_id'];
				break;
			}
		}
	}
	return $album_id;
} // getAlbumID()

function manufaktur_gallery_droplet_search($page_id, $page_url) { 
	
	$result = array();
	
	$album_id = getAlbumID($page_id);
	// keine Galerie gefunden?
	if (empty($album_id)) return $result;

	$contents = file_get_contents("http://graph.facebook.com/$album_id");
	$album = json_decode($contents, true);
	$album_name = $album['name'];
	$album_description = (isset($album['description'])) ? $album['description'] : '';
	$album_image = "http://graph.facebook.com/$album_id/picture?type=thumbnail";
	$comments = (isset($album['comments'])) ? $album['comments']['data'] : array();
	
	$parser = new Dwoo();
	$htt_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt/';
  $tpl_title = new Dwoo_Template_File($htt_path.'search.result.title.htt');
  $tpl_description = new Dwoo_Template_File($htt_path.'search.result.description.htt');
  
  $result = array();
  // erster Eintrag mit Titel und Beschreibung des Albums
  $all_comments = ''; 
  foreach ($comments as $comment) {
  	$all_comments .= $comment['from']['name'].' - '.$comment['message'];
  }
  $result[] = array(
  	'url'						=> $page_url,
  	'params'				=> '',
  	'title'					=> $parser->get($tpl_title, array('title' => $album_name)),
  	'description'		=> $parser->get($tpl_description, array('gallery_image'	=> $album_image, 'description' => $album_description)),
  	'text'					=> strip_tags("$album_name - $album_description - $all_comments"),
  	'modified_when'	=> strtotime($album['updated_time']),
  	'modified_by'		=> 1																					
  );
  
  $contents = file_get_contents(sprintf("http://graph.facebook.com/%s/photos?limit=%d&offset=%d", 
																				$album_id,
																				200,
																				0));
	$photos = json_decode($contents,true);
	if (isset($photos['error'])) {
		trigger_error(sprintf(gallery_error_fb_prompt_error, $album['error']['message']));
		return false;
	}

	foreach ($photos['data'] as $photo) {
		$i = count($photo['images'])-1;
		$description = (isset($photo['name'])) ? $photo['name'] : '';
		$all_comments = '';
		$comments = (isset($photo['comments'])) ? $photo['comments']['data'] : array();
		foreach ($comments as $comment) {
			$all_comments .= $comment['from']['name'].' - '.$comment['message'];
		}
		if (empty($description) && empty($all_comments)) continue;
		if (empty($description)) $description = $album_description;
		$result[] = array(
			'url'						=> $page_url,
	  	'params'				=> http_build_query(array('position' => $photo['position'])),
	  	'title'					=> $parser->get($tpl_title, array('title' => $album_name)),
	  	'description'		=> $parser->get($tpl_description, array('gallery_image'	=> $photo['images'][$i]['source'], 'description' => $description)),
	  	'text'					=> strip_tags("$description - $all_comments"),
	  	'modified_when'	=> strtotime($photo['created_time']),
	  	'modified_by'		=> 1
		);
	}
	return $result;
} // manufaktur_gallery_droplet_search()

function manufaktur_gallery_droplet_header($page_id) {
	
	$result = array();
	
	$album_id = getAlbumID($page_id);
	// keine Galerie gefunden?
	if (empty($album_id)) return $result;

	$contents = file_get_contents("http://graph.facebook.com/$album_id");
	$album = json_decode($contents, true);
	$album_name = $album['name'];
	$album_description = (isset($album['description'])) ? $album['description'] : '';
	
	$result = array(
		'title'				=> $album_name,
		'description'	=> $album_description,
		'keywords'		=> '',
	);
	return $result;
} // manufaktur_gallery_droplet_header()

?>