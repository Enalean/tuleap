<?php
require_once('pre.php');
require_once('my_utils.php');
require_once('common/widget/WidgetLayoutManager.class.php');

$em =& EventManager::instance();
$em->processEvent('plugin_load_language_file', null);

$request =& HTTPRequest::instance();
$layout_id = $request->get('layout_id');
if (user_isloggedin() && $layout_id) {
    
    $title = $Language->getText('my_index', 'title', array(user_getrealname(user_getid()).' ('.user_getname().')'));
    my_header(array('title'=>$title));
    
    $lm =& new WidgetLayoutManager();
    $lm->displayAvailableWidgets(user_getid(), $lm->OWNER_TYPE_USER, $layout_id);
    
    site_footer(array());

} else {
    exit_not_logged_in();
}
?>
