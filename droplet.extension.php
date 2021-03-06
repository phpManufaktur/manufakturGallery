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

require_once(WB_PATH.'/modules/dwoo/include.php');

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

		$SQL = "SELECT `text` FROM `".TABLE_PREFIX."mod_wysiwyg` WHERE `page_id`='$page_id'";
		if (null == ($query = $database->query($SQL))) {
		  trigger_error(sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $database->get_error()), E_USER_ERROR);
		  return false;
		}

		$album_id = '';

		while (false !== ($section = $query->fetchRow(MYSQL_ASSOC))) {
			if (false !== ($start = strpos($section['text'], '[[manufaktur_gallery?'))) {
				$start = $start+strlen('[[manufaktur_gallery?');
				$end = strpos($section['text'], ']]', $start);
				$param_str = substr($section['text'], $start, $end-$start);
				$param_str = str_ireplace('&amp;', '&', $param_str);
				parse_str($param_str, $params);
				if (isset($params['album_id'])) {
					$album_id = $params['album_id'];
					break;
				}
			}
		}
		if (empty($album_id)) {
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
						$start = $start+strlen('[[manufaktur_gallery?');
						$end = strpos($section['content_long'], ']]', $start);
						$param_str = substr($section['content_long'], $start, $end-$start);
						$param_str = str_ireplace('&amp;', '&', $param_str);
						parse_str($param_str, $params);
						if (isset($params['album_id'])) {
						  // get the album ID
							$album_id = $params['album_id'];
							// get the TOPICS directory
							global $topics_directory;
							include_once WB_PATH . '/modules/topics/module_settings.php';
							// change the page URL
							$page_url = WB_URL . $topics_directory . $section['link'] . PAGE_EXTENSION;
							// leave the loop
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