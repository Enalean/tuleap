<?php
require_once('pre.php');
require_once('www/my/my_utils.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('common/widget/Valid_Widget.class.php');
$GLOBALS['HTML']->includeJavascriptFile('/scripts/codendi/LayoutManager.js');
$hp = Codendi_HTMLPurifier::instance();
if (user_isloggedin()) {
    
    $request        = HTTPRequest::instance();
    $csrk_token     = new CSRFSynchronizerToken('widget_management');
    $layout_manager = new WidgetLayoutManager();
    $vLayoutId      = new Valid_UInt('layout_id');
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
                case WidgetLayoutManager::OWNER_TYPE_USER:
                    $owner_id = user_getid();
                    
                    $title = $Language->getText('my_index', 'title', array( $hp->purify(user_getrealname(user_getid()), CODENDI_PURIFIER_CONVERT_HTML) .' ('.user_getname().')'));
                    my_header(array('title'=>$title, 'selected_top_tab' => '/my/'));
                    $layout_manager->displayAvailableWidgets(
                        user_getid(),
                        WidgetLayoutManager::OWNER_TYPE_USER,
                        $layout_id,
                        $csrk_token
                    );
                    site_footer(array());
                    
                    break;
                case WidgetLayoutManager::OWNER_TYPE_GROUP:
                    $pm = ProjectManager::instance();
                    if ($project = $pm->getProject($owner_id)) {
                        $group_id = $owner_id;
                        $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
                        $request->params['group_id'] = $group_id; //bad!
                        if (user_ismember($group_id, 'A') || user_is_super_user()) {
                            $title = $Language->getText('include_project_home','proj_info').' - '. $project->getPublicName();
                            site_project_header(array('title'=>$title,'group'=>$group_id,'toptab'=>'summary'));
                            $layout_manager->displayAvailableWidgets(
                                $group_id,
                                WidgetLayoutManager::OWNER_TYPE_GROUP,
                                $layout_id,
                                $csrk_token
                            );
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
