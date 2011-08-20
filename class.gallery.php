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

// include GENERAL language file
if(!file_exists(WB_PATH .'/modules/kit_tools/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/kit_tools/languages/DE.php'); // Vorgabe: DE verwenden 
}
else {
	require_once(WB_PATH .'/modules/kit_tools/languages/' .LANGUAGE .'.php');
}

// include language file for the gallery
if(!file_exists(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php')) {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/DE.php'); // Vorgabe: DE verwenden 
	if (!defined('GALLERY_LANGUAGE')) define('GALLERY_LANGUAGE', 'DE'); // die Konstante gibt an in welcher Sprache manufakturGallery aktuell arbeitet
}
else {
	require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/languages/' .LANGUAGE .'.php');
	if (!defined('GALLERY_LANGUAGE')) define('GALLERY_LANGUAGE', LANGUAGE); 
}

if (!class_exists('Dwoo')) 								require_once(WB_PATH.'/modules/dwoo/include.php');
if (!class_exists('kitToolsLibrary'))   	require_once(WB_PATH.'/modules/kit_tools/class.tools.php');
if (!class_exists('Facebook')) 						require_once(WB_PATH .'/modules/'.basename(dirname(__FILE__)).'/facebook-sdk/src/facebook.php');

global $kitLibrary;
global $parser;
global $facebook;

if (!is_object($kitLibrary)) 	$kitLibrary = new kitToolsLibrary();
if (!is_object($parser)) 			$parser = new Dwoo();

// Connect with DropletsExtension Interface
if (file_exists(WB_PATH.'/modules/droplets_extension/interface.php')) {
  require_once(WB_PATH.'/modules/droplets_extension/interface.php');
}

class Gallery {
	const request_action							= 'act';
	const request_offset							= 'offset';
	const request_position						= 'position';
	
	const action_default							= 'def';
	const action_list									= 'list';
	
	private $page_link 								= '';
	private $template_path						= '';
	private $error										= '';
	private $message									= '';
	
	const param_preset								= 'preset';
	const param_css										= 'css';
	const param_album_id							= 'album_id';
	const param_action								= 'action';
	const param_facebook_id						= 'facebook_id';
	const param_limit									= 'limit';
	const param_columns								= 'columns';
	const param_photo_description			= 'photo_description';
	const param_photo_comments				= 'photo_comments';
	const param_album_comments				= 'album_comments';
	const param_merge_comments				= 'merge_comments'; 
	const param_search								= 'search';
	const param_page_header						= 'page_header';
	
	private $params = array(
		self::param_preset							=> 1,
		self::param_css									=> true, 
		self::param_album_id						=> '',
		self::param_action							=> self::action_default,
		self::param_facebook_id					=> '',
		self::param_limit								=> 24,
		self::param_columns							=> 3,
		self::param_photo_description		=> false,
		self::param_photo_comments			=> false,
		self::param_album_comments			=> true,
		self::param_merge_comments			=> false,
		self::param_search							=> true,
		self::param_page_header					=> true
	);
	
	public function __construct() {
		global $kitLibrary;
		$url = '';
		$_SESSION['FRONTEND'] = true;	
		$kitLibrary->getPageLinkByPageID(PAGE_ID, $url);
		$this->page_link = $url; 
		$this->template_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt/'.$this->params[self::param_preset].'/'.GALLERY_LANGUAGE.'/' ;
		date_default_timezone_set(tool_cfg_time_zone);		
	} // __construct()
	
	public function getParams() {
		return $this->params;
	} // getParams()
	
	public function setParams($params = array()) {
		$this->params = $params;
		$this->template_path = WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/htt/'.$this->params[self::param_preset].'/'.GALLERY_LANGUAGE.'/';
		if (!file_exists($this->template_path)) {
			$this->setError(sprintf(gallery_error_preset_not_exists, '/modules/'.basename(dirname(__FILE__)).'/htt/'.$this->params[self::param_preset].'/'.GALLERY_LANGUAGE.'/'));
			return false;
		}
		return true;
	} // setParams()
	
	/**
    * Set $this->error to $error
    * 
    * @param STR $error
    */
  public function setError($error) {
  	$this->error = $error;
  } // setError()

  /**
    * Get Error from $this->error;
    * 
    * @return STR $this->error
    */
  public function getError() {
    return $this->error;
  } // getError()

  /**
    * Check if $this->error is empty
    * 
    * @return BOOL
    */
  public function isError() {
    return (bool) !empty($this->error);
  } // isError

  /**
   * Reset Error to empty String
   */
  public function clearError() {
  	$this->error = '';
  }

  /** Set $this->message to $message
    * 
    * @param STR $message
    */
  public function setMessage($message) {
    $this->message = $message;
  } // setMessage()

  /**
    * Get Message from $this->message;
    * 
    * @return STR $this->message
    */
  public function getMessage() {
    return $this->message;
  } // getMessage()

  /**
    * Check if $this->message is empty
    * 
    * @return BOOL
    */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage
  
  /**
   * Gibt das gewuenschte Template zurueck
   * 
   * @param STR $template
   * @param ARRAY $template_data
   */
  public function getTemplate($template, $template_data) {
  	global $parser;
  	try {
  		$result = $parser->get($this->template_path.$template, $template_data); 
  	} catch (Exception $e) {
  		$this->setError(sprintf(form_error_template_error, $template, $e->getMessage()));
  		return false;
  	}
  	return $result;
  } // getTemplate()
	
	
	public function getFacebookSDKversion() {
		return Facebook::VERSION;	
	} // getFacebookSDKversion
	
	/**
   * Verhindert XSS Cross Site Scripting
   * 
   * @param REFERENCE ARRAY $request
   * @return ARRAY $request
   */
	public function xssPrevent(&$request) { 
  	if (is_string($request)) {
	    $request = html_entity_decode($request);
	    $request = strip_tags($request);
	    $request = trim($request);
	    $request = stripslashes($request);
  	}
	  return $request;
  } // xssPrevent()
	
	public function action() {
		$html_allowed = array();
  	foreach ($_REQUEST as $key => $value) {
  		if (!in_array($key, $html_allowed)) {
  			$_REQUEST[$key] = $this->xssPrevent($value);	  			
  		} 
  	}
  	// Welche Aktion soll ausgefuehrt werden?
  	$action = (isset($_REQUEST[self::request_action])) ? $_REQUEST[self::request_action] : $this->params[self::param_action];
  
  	// CSS laden? 
    if ($this->params[self::param_css]) { 
			if (!is_registered_droplet_css('manufaktur_gallery', PAGE_ID)) { 
	  		register_droplet_css('manufaktur_gallery', PAGE_ID, 'manufaktur_gallery', 'frontend.css');
			}
    }
    elseif (is_registered_droplet_css('manufaktur_gallery', PAGE_ID)) {
		  unregister_droplet_css('manufaktur_gallery', PAGE_ID);
    }
    
    // in die Suchfunktion integrieren?
    if ($this->params[self::param_search]) {
    	// Register Droplet for the WebsiteBaker Search Function
			if (!is_registered_droplet_search('manufaktur_gallery', PAGE_ID)) { 
  			register_droplet_search('manufaktur_gallery', PAGE_ID, 'manufaktur_gallery');
			}
    }
    else {
	    if (is_registered_droplet_search('manufaktur_gallery', PAGE_ID)) {
	  		unregister_droplet_search('manufaktur_gallery', PAGE_ID);
			}
	  }
	  
	  // Seiteninformationen bereitstellen?
	  if ($this->params[self::param_page_header]) {
	  	if (!is_registered_droplet_header('manufaktur_gallery', PAGE_ID)) {
 				register_droplet_header('manufaktur_gallery', PAGE_ID, 'manufaktur_gallery');
	  	}
	  }
	  else {
	  	if (is_registered_droplet_header('manufaktur_gallery', PAGE_ID)) {
  			unregister_droplet_header('manufaktur_gallery', PAGE_ID);
			}
	  }
    
    switch($action):
		case self::action_list:
			// verfuegbare Alben anzeigen
			$result = $this->showFBinfo();
			break;
		default:
			// Galerie anzeigen
			$result = $this->showAlbum();
			break;
    endswitch;
    		
    return $this->show($result);
	} // action()
	
	public function show($content) {
		$data = array(
  		'error'				=> ($this->isError()) ? 1 : 0,
  		'content'			=> ($this->isError()) ? $this->getError() : $content
  	);
  	return $this->getTemplate('body.htt', $data);
	}
	
	public function showAlbum() {
		$album_id = isset($this->params[self::param_album_id]) ? $this->params[self::param_album_id] : '';
		
		if (empty($album_id)) {
			$this->setError(gallery_error_missing_album_id);
			return false;
		}
		$old_error_reporting = error_reporting(0);
		if (ini_get('allow_url_fopen') == 1) {
			// file_get_contents kann verwendet werden
			if (false === ($contents = file_get_contents("http://graph.facebook.com/$album_id"))) {
				$error = error_get_last();
				$this->setError(sprintf(gallery_error_request_album_id, $album_id, $error['message']));
				return false;
			}
		}
		elseif (in_array('curl', get_loaded_extensions())) {
			// cURL verwenden
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://graph.facebook.com/$album_id");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			if (false === ($contents = curl_exec($ch))) {
				$this->setError(curl_error($ch));
				curl_close($ch);
				return false;
			}
			curl_close($ch); 
		}
		else {
			// keine geeignete Methode gefunden
			$this->setError(gallery_error_no_http_request);
			return false;
		}		
		error_reporting($old_error_reporting);
		
		$comments = array();
		$album = json_decode($contents, true);
		if (isset($album['error'])) {
			$this->setError(sprintf(gallery_error_fb_prompt_error, $album['error']['message']));
			return false;
		}
		if (isset($album['comments']) && $this->params[self::param_album_comments]) {
			foreach ($album['comments']['data'] as $comment) {
				$comments[] = $comment;
			}
		}
		
		$offset = isset($_REQUEST[self::request_offset]) ? $_REQUEST[self::request_offset] : 0;
		$position = isset($_REQUEST[self::request_position]) ? $_REQUEST[self::request_position] : -1;
		if ($position > $this->params[self::param_limit]) {
			// an eine bestimmte Stelle im Album springen
			$offset = 0;
			while ($offset <= $position) {
				$offset = $offset + $this->params[self::param_limit];
			}
			$offset = $offset - $this->params[self::param_limit];
		}
		$url = sprintf("http://graph.facebook.com/%s/photos?limit=%d&offset=%d", $album_id, $this->params[self::param_limit],	$offset);
		if (ini_get('allow_url_fopen') == 1) {
			// file_get_contents kann verwendet werden
			if (false === ($contents = file_get_contents($url))) {
				$error = error_get_last();
				$this->setError(sprintf(gallery_error_request_album_id, $album_id, $error['message']));
				return false;
			}
		}
		elseif (in_array('curl', get_loaded_extensions())) {
			// cURL verwenden
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			if (false === ($contents = curl_exec($ch))) {
				$this->setError(curl_error($ch));
				curl_close($ch);
				return false;
			}
			curl_close($ch); 
		}
		else {
			// keine geeignete Methode gefunden
			$this->setError(gallery_error_no_http_request);
			return false;
		}		
		
		$photos = json_decode($contents,true);
		if (isset($photos['error'])) {
			$this->setError(sprintf(gallery_error_fb_prompt_error, $album['error']['message']));
			return false;
		}
		$paging = isset($photos['paging']) ? $photos['paging'] : array();
		$photos = isset($photos['data']) ? $photos['data'] : array();
		$images = array();
		foreach ($photos as $photo) {
			$img = 1;
		  for ($i = 0; $i < 5; $i++) {
		  	if (isset($photo['images'][$i]['width']) && ($photo['images'][$i]['width'] == 180)) {
		  		$img = $i;
		  		break;
		  	}
		  }
		  $photo_comments = isset($photo['comments']) ? $photo['comments']['data'] : array();
		  $images[] = array(
		  	'zoom_url'					=> $photo['images'][0]['source'],
		  	'zoom_width'				=> $photo['images'][0]['width'],
		  	'zoom_height'				=> $photo['images'][0]['height'],
		  	'image_url'					=> $photo['images'][$img]['source'],
		  	'image_width'				=> $photo['images'][$img]['width'],
		  	'image_height'			=> $photo['images'][$img]['height'],
		  	'image_description'	=> isset($photo['name']) ? $photo['name'] : '' ,
		 		'comments'					=> $photo_comments,
		  	'selected'					=> $position == $photo['position'] ? 1 : 0 										 
			); 
			if (!empty($photo_comments) && $this->params[self::param_merge_comments]) {
				foreach ($photo_comments as $comment) {
					$comments[] = $comment;
				}
			}
		}
		$next_page = '';
		$previous_page = '';
		if (isset($paging['next'])) { 
			parse_str($paging['next'], $next);
			$next_page = sprintf('%s%s%s=%s', $this->page_link, (strpos($this->page_link, '?') === false) ? '?' : '&', self::request_offset, $next['offset']);
		}	
		if (isset($paging['previous'])) {
			parse_str($paging['previous'], $previous);
			$previous_page = sprintf('%s%s%s=%s', $this->page_link, (strpos($this->page_link, '?') === false) ? '?' : '&', self::request_offset, $previous['offset']);
		}	
		$album['comments'] = $comments;
		$data = array(
			'album'							=> $album,
			'photos'						=> $images,
			'columns'						=> $this->params[self::param_columns],
			'photo_description'	=> ($this->params[self::param_photo_description] == true) ? 1 : 0,
			'photo_comments'		=> ($this->params[self::param_photo_comments] == true) ? 1 : 0,
			'album_comments'		=> ($this->params[self::param_album_comments] == true) ? 1 : 0,
			'merge_comments'		=> ($this->params[self::param_merge_comments] == true) ? 1 : 0,
			//'comments'					=> $comments,
			'next_page'					=> $next_page,
			'previous_page'			=> $previous_page
		);
		return $this->getTemplate('gallery.htt', $data);
	} // showAlbum()
	
	/**
	 * Zeigt die zu der Facebook ID verfuegbaren Foto Alben an
	 */
	public function showFBinfo() {
		$facebook_id = isset($this->params[self::param_facebook_id]) ? $this->params[self::param_facebook_id] : '';
		if (empty($facebook_id)) {
			$this->setError(gallery_error_missing_fb_id);
			return false;
		}
		if (ini_get('allow_url_fopen') == 1) {
			// file_get_contents kann verwendet werden
			if (false === ($contents = file_get_contents(sprintf('http://graph.facebook.com/%s/albums?fields=id,name,type&limit=1000', $facebook_id)))) {
				$this->setError(gallery_error_get_contents);
				return false;
			}
		}
		elseif (in_array('curl', get_loaded_extensions())) {
			// cURL verwenden
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, sprintf('http://graph.facebook.com/%s/albums?fields=id,name,type&limit=1000', $facebook_id));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			if (false === ($contents = curl_exec($ch))) {
				$this->setError(curl_error($ch));
				curl_close($ch);
				return false;
			}
			curl_close($ch); 
		}
		else {
			// keine geeignete Methode gefunden
			$this->setError(gallery_error_no_http_request);
			return false;
		}
		
		$albums = json_decode($contents,true);
		if (isset($album['error'])) {
			$this->setError(sprintf(gallery_error_fb_prompt_error, $album['error']['message']));
			return false;
		}
		if (!isset($albums['data'])) {
			$this->setError(sprintf(gallery_error_no_gallery, $facebook_id));
			return false;
		}
		$albums = $albums['data'];
  	$galleries = array(); 
		foreach ($albums as $row) {
			$galleries[] = array(
				'type'		=> $row['type'],
				'id'			=> $row['id'],
				'name'		=> $row['name']
			);
		}
		$data = array(
			'facebook_id'	=> $facebook_id,
			'galleries'		=> $galleries
		);
		return $this->getTemplate('list.htt', $data);
	} // showFBinfo()
	
} // class gallery

?>