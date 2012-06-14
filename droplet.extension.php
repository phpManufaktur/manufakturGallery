<?php

/**
 * manufakturGallery
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2011 - 2012
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License (GPL)
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

if (!function_exists('getAlbumID')) {
	function getAlbumID($page_id, &$params=array(), &$page_url='') {
		global $database;
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

		if (empty($album_id) && defined('TOPIC_ID')) {
			// kein Album gefunden, moeglicherweise TOPICS!
			$SQL = sprintf("SHOW TABLE STATUS LIKE '%smod_topics'", TABLE_PREFIX);
			$query = $database->query($SQL);
			if ($query->numRows() > 0) {
				// TOPICS ist installiert
				$SQL = sprintf(	"SELECT topic_id, content_long, link FROM %smod_topics WHERE page_id='%s' AND (content_long LIKE '%%[[manufaktur_gallery?%%')",
												TABLE_PREFIX,
												$page_id);
				$query = $database->query($SQL);
				while (false !== ($section = $query->fetchRow(MYSQL_ASSOC))) {
					if (false !== ($start = strpos($section['content_long'], '[[manufaktur_gallery?'))) {
						// Droplet gefunden
						if (TOPIC_ID != $section['topic_id']) continue;
						$start = $start+strlen('[[manufaktur_gallery?');
						$end = strpos($section['content_long'], ']]', $start);
						$param_str = substr($section['content_long'], $start, $end-$start);
						$param_str = str_ireplace('&amp;', '&', $param_str);
						parse_str($param_str, $params);
						if (isset($params['album_id'])) {
							$album_id = $params['album_id'];
							$page_url = WB_URL.PAGES_DIRECTORY.'/topics/'.$section['link'].PAGE_EXTENSION;
							break;
						}
					}
				}
			}
		}
		return $album_id;
	} // getAlbumID()
}

if (!function_exists('manufaktur_gallery_droplet_search')) {
	function manufaktur_gallery_droplet_search($page_id, $page_url) {

		$result = array();

		$album_id = getAlbumID($page_id, $params, $page_url);
		// keine Galerie gefunden?
		if (empty($album_id)) return $result;

		//$contents = file_get_contents("http://graph.facebook.com/$album_id");

		$old_error_reporting = error_reporting(0);
		if (ini_get('allow_url_fopen') == 1) {
		  // file_get_contents kann verwendet werden
		  if (false === ($contents = file_get_contents("http://graph.facebook.com/$album_id"))) {
		    $error = error_get_last();
		    trigger_error(sprintf(gallery_error_request_album_id, $album_id, $error['message']), E_USER_ERROR);
		    return false;
		  }
		}
		elseif (in_array('curl', get_loaded_extensions())) {
		  // cURL verwenden
		  $ch = curl_init();
		  curl_setopt($ch, CURLOPT_URL, "http://graph.facebook.com/$album_id");
		  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		  if (false === ($contents = curl_exec($ch))) {
		    trigger_error(curl_error($ch), E_USER_ERROR);
		    curl_close($ch);
		    return false;
		  }
		  curl_close($ch);
		}
		else {
		  // keine geeignete Methode gefunden
		  trigger_error(gallery_error_no_http_request, E_USER_ERROR);
		  return false;
		}
		error_reporting($old_error_reporting);

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
	  	'params'				=> '#mg',
	  	'title'					=> $parser->get($tpl_title, array('title' => $album_name)),
	  	'description'		=> $parser->get($tpl_description, array('gallery_image'	=> $album_image, 'description' => $album_description, 'page_url' => $page_url.'#mg')),
	  	'text'					=> strip_tags("$album_name - $album_description - $all_comments"),
	  	'modified_when'	=> strtotime($album['updated_time']),
	  	'modified_by'		=> 1
	  );

	  //$contents = file_get_contents(sprintf("http://graph.facebook.com/%s/photos?limit=%d&offset=%d",	$album_id, 200,	0));

	  $old_error_reporting = error_reporting(0);
	  if (ini_get('allow_url_fopen') == 1) {
	    // file_get_contents kann verwendet werden
	    if (false === ($contents = file_get_contents(sprintf("http://graph.facebook.com/%s/photos?limit=%d&offset=%d",	$album_id, 200,	0)))) {
	      $error = error_get_last();
	      trigger_error(sprintf(gallery_error_request_album_id, $album_id, $error['message']), E_USER_ERROR);
	      return false;
	    }
	  }
	  elseif (in_array('curl', get_loaded_extensions())) {
	    // cURL verwenden
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, sprintf("http://graph.facebook.com/%s/photos?limit=%d&offset=%d",	$album_id, 200,	0));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    if (false === ($contents = curl_exec($ch))) {
	      trigger_error(curl_error($ch), E_USER_ERROR);
	      curl_close($ch);
	      return false;
	    }
	    curl_close($ch);
	  }

		$photos = json_decode($contents,true);
		if (isset($photos['error'])) {
			trigger_error(sprintf(gallery_error_fb_prompt_error, $album['error']['message']), E_USER_ERROR);
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
			$img_link = sprintf('%s?position=%s#mg', $page_url, $photo['position']);
			$result[] = array(
				'url'						=> $page_url,
		  	'params'				=> http_build_query(array('position' => $photo['position'])).'#mg',
		  	'title'					=> $parser->get($tpl_title, array('title' => $album_name)),
		  	'description'		=> $parser->get($tpl_description, array('gallery_image'	=> $photo['images'][$i]['source'], 'description' => $description, 'page_url' => $img_link)),
		  	'text'					=> strip_tags("$description - $all_comments"),
		  	'modified_when'	=> strtotime($photo['created_time']),
		  	'modified_by'		=> 1
			);
		}
		return $result;
	} // manufaktur_gallery_droplet_search()
}

if (!function_exists('manufaktur_gallery_droplet_header')) {
	function manufaktur_gallery_droplet_header($page_id) {
		$result = array();
		$album_id = getAlbumID($page_id);
		// keine Galerie gefunden?
		if (empty($album_id)) return $result;

		$old_error_reporting = error_reporting(0);
		if (false == ($contents = file_get_contents("http://graph.facebook.com/$album_id"))) {
			// Fehler bei der Abfrage, in diesem Fall keine Meldung - leeres Array zurueckgeben!
			return $result;
		}
		error_reporting($old_error_reporting);


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
}

?>