//:interface to manufakturGallery
//:Please visit http://phpManufaktur.de for informations about manufakturGallery!
/**
 * manufakturGallery
 * 
 * @author Ralf Hertsch (ralf.hertsch@phpmanufaktur.de)
 * @link http://phpmanufaktur.de
 * @copyright 2011
 * @license GNU GPL (http://www.gnu.org/licenses/gpl.html)
 * @version $Id$
 */
if (file_exists(WB_PATH.'/modules/manufaktur_gallery/class.gallery.php')) {
	require_once(WB_PATH.'/modules/manufaktur_gallery/class.gallery.php');
	$gallery = new Gallery();
	$params = $gallery->getParams();
	$params[Gallery::param_preset] = (isset($preset)) ? (int) $preset : 1;
	$params[Gallery::param_album_id] = (isset($album_id)) ? $album_id : '';
	$params[Gallery::param_action] = (isset($action)) ? $action : Gallery::action_default;
	$params[Gallery::param_css] = (isset($css) && strtolower($css) == 'false') ? false : true;
	$params[Gallery::param_photo_description] = (isset($photo_description) && strtolower($photo_description) == 'true') ? true : false;
	$params[Gallery::param_photo_comments] = (isset($photo_comments) && strtolower($photo_comments) == 'true') ? true : false;
	$params[Gallery::param_album_comments] = (isset($album_comments) && strtolower($album_comments) == 'false') ? false : true;
	$params[Gallery::param_page_header] = (isset($page_header) && strtolower($page_header) == 'false') ? false : true;
	$params[Gallery::param_search] = (isset($search) && strtolower($search) == 'false') ? false : true;
	$params[Gallery::param_merge_comments] = (isset($merge_comments) && strtolower($merge_comments) == 'true') ? true : false;	  
	$params[Gallery::param_facebook_id] = (isset($facebook_id)) ? $facebook_id : '';
	$params[Gallery::param_limit] = (isset($limit)) ? (int) $limit : 24;
	$params[Gallery::param_columns] = (isset($columns)) ? (int) $columns : 3;
  if (!$gallery->setParams($params)) return $gallery->getError();
	return $gallery->action();
}
else {
	return "manufakturGallery is not installed!";
}
