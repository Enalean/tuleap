<?php
require_once('pre.php');
require_once('www/my/my_utils.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('common/widget/Valid_Widget.class.php');

if (user_isloggedin()) {

    $em =& EventManager::instance();
    $em->processEvent('plugin_load_language_file', null);
    
    $request =& HTTPRequest::instance();
    $lm =& new WidgetLayoutManager();
    $vLayoutId = new Valid_UInt('layout_id');
    $vLayoutId->required();
    if ($request->valid($vLayoutId)) {
        $layout_id = $request->get('layout_id');

        $vOwner = new Valid_Widget_Owner('owner');
        $vOwner->required();
        if ($request->valid($vOwner)) {
            $owner = $request->get('owner');
            $owner_id   = (int)substr($owner, 1);
            $owner_type = substr($owner, 0, 1);
            switch($owner_type) {
                case $lm->OWNER_TYPE_USER:
                    $owner_id = user_getid();
                    
                    $title = $Language->getText('my_index', 'title', array(user_getrealname(user_getid()).' ('.user_getname().')'));
                    my_header(array('title'=>$title));
                    $lm->displayAvailableWidgets(user_getid(), $lm->OWNER_TYPE_USER, $layout_id);
                    site_footer(array());
                    
                    break;
                case $lm->OWNER_TYPE_GROUP:
                    if ($project = project_get_object($owner_id)) {
                        $group_id = $owner_id;
                        $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
                        $request->params['group_id'] = $group_id; //bad!
                        if (user_ismember($group_id, 'A') || user_is_super_user()) {
                            $title = $Language->getText('include_project_home','proj_info').' - '. $project->getPublicName();
                            site_project_header(array('title'=>$title,'group'=>$group_id,'toptab'=>'summary'));
                            $lm->displayAvailableWidgets($group_id, $lm->OWNER_TYPE_GROUP, $layout_id);
                            site_footer(array());
                        } else {
                            $GLOBALS['Response']->redirect('/projects/'.$project->getUnixName().'/');
                        }
                    }
                    break;
                default:
                    break;
            }
        }
    }
} else {
    exit_not_logged_in();
}
?>
