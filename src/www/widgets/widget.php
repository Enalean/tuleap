<?php

require_once('pre.php');
session_write_close();
require_once('www/my/my_utils.php');
require_once('common/widget/Widget.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('common/widget/Valid_Widget.class.php');

use Tuleap\Widget\WidgetFactory;

$layout_manager = new WidgetLayoutManager();
$csrk_token     = new CSRFSynchronizerToken('widget_management');
$request        = HTTPRequest::instance();
$good           = false;
$redirect       = '/';
$widget_factory = new WidgetFactory(
    UserManager::instance(),
    new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
    EventManager::instance()
);

$vOwner = new Valid_Widget_Owner('owner');
$vOwner->required();
if ($request->valid($vOwner)) {
    $owner = $request->get('owner');
    $owner_id   = (int)substr($owner, 1);
    $owner_type = substr($owner, 0, 1);
    switch($owner_type) {
        case WidgetLayoutManager::OWNER_TYPE_USER:
            $owner_id = user_getid();
            $redirect = '/my/';
            $good = true;
            break;
        case WidgetLayoutManager::OWNER_TYPE_GROUP:
            $pm = ProjectManager::instance();
            if ($project = $pm->getProject($owner_id)) {
                $group_id = $owner_id;
                $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
                $request->params['group_id'] = $group_id; //bad!
                $redirect = '/projects/'. $project->getUnixName();
                $good = true;
            }
            break;
        default:
            break;
    }
    if ($good) {
        if ($request->exist('name')) {
            $param = $request->get('name');
            $name = array_pop(array_keys($param));
            $instance_id = (int)$param[$name];
            if ($widget = $widget_factory->getInstanceByWidgetName($name)) {
                if ($widget->isAvailable()) {
                    switch ($request->get('action')) {
                        case 'update':
                            if ($layout_id = (int)$request->get('layout_id')) {
                                $csrk_token->check($redirect, $request);
                                if ($owner_type == WidgetLayoutManager::OWNER_TYPE_USER || user_ismember($group_id, 'A') || user_is_super_user()) {
                                    if ($request->get('cancel') || $widget->updatePreferences($request)) {
                                        $layout_manager->hideWidgetPreferences($owner_id, $owner_type, $layout_id, $name, $instance_id);
                                    }
                                }
                            }
                            break;
                        case 'ajax':
                            if ($widget->isAjax()) {
                                $widget->loadContent($instance_id);
                                echo $widget->getContent();
                                //Layout::showDebugInfo();
                                exit();
                            }
                            break;
                        case 'iframe':
                            echo '<html><head>';
                            $GLOBALS['HTML']->displayStylesheetElements(array());
                            echo '</head><body class="main_body_row contenttable">';
                            $widget->loadContent($instance_id);
                            echo $widget->getContent();
                            echo '</body></html>';
                            exit;
                            break;
                        case 'process':
                            $widget->loadContent($instance_id);
                            $widget->process($owner_type, $owner_id);
                            exit;
                        default:
                            break;
                    }
                }
            }
        }
    }
}
if (!$request->isAjax()) {
    $GLOBALS['Response']->redirect($redirect);
}
?>
