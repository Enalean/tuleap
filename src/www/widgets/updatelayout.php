<?php
require_once('pre.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('common/widget/Widget.class.php');

$request = HTTPRequest::instance();

$lm = new WidgetLayoutManager();
$good = false;
$redirect   = '/';
$owner = $request->get('owner');

if ($owner) {
    $owner_id   = (int)substr($owner, 1);
    $owner_type = substr($owner, 0, 1);
    switch($owner_type) {
        case WidgetLayoutManager::OWNER_TYPE_USER:
            $owner_id = user_getid();
            $layout_id =(int)$request->get('layout_id');
            $redirect = '/my/';
            $good = true;
            break;
        case WidgetLayoutManager::OWNER_TYPE_GROUP:
            $pm = ProjectManager::instance();
            if ($project = $pm->getProject($owner_id)) {
                $group_id = $owner_id;
                $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
                $request->params['group_id'] = $group_id; //bad!
                $redirect = '/projects/'. $project->getUnixName().'/';
                if (!user_ismember($group_id, 'A') && !user_is_super_user()) {
                    $GLOBALS['Response']->redirect($redirect);
                }
                $good = true;
            }
            break;
        default:
            break;
    }
    if ($good) {
        if (!$request->exist('layout_id')) {
            //Search the default one
            $layout_id = $lm->getDefaultLayoutId($owner_id, $owner_type);
        } else {
            $layout_id = (int)$request->get('layout_id');
        }
        if ($layout_id || $request->get('action') == 'preferences') {
            $name = null;
            if ($request->exist('name')) {
                $param = $request->get('name');
                $name = array_pop(array_keys($param));
                $instance_id = (int)$param[$name];
            }
            switch($request->get('action')) {
                case 'widget':
                    if ($name && $layout_id) {
                        if ($widget = Widget::getInstance($name)) {
                            if ($widget->isAvailable()) {
                                $action = array_pop(array_keys($param[$name]));
                                switch($action) {
                                    case 'remove':
                                        $instance_id = (int)$param[$name][$action];
                                        $lm->removeWidget($owner_id, $owner_type, $layout_id, $name, $instance_id, $widget);
                                        break;
                                    case 'add':
                                    default:
                                        $lm->addWidget($owner_id, $owner_type, $layout_id, $name, $widget, $request);
                                        break;
                                }
                            }
                        }
                    }
                    break;
                case 'minimize':
                    if ($name) {
                        $lm->mimizeWidget($owner_id, $owner_type, $layout_id, $name, $instance_id);
                    }
                    break;
                case 'maximize':
                    if ($name) {
                        $lm->maximizeWidget($owner_id, $owner_type, $layout_id, $name, $instance_id);
                    }
                    break;
                case 'preferences':
                    if ($name) {
                        $lm->displayWidgetPreferences($owner_id, $owner_type, $layout_id, $name, $instance_id);
                    }
                    break;
                case 'layout':
                    $lm->updateLayout($owner_id, $owner_type, $request->get('layout_id'), $request->get('new_layout'));
                    break;
                default:
                    $lm->reorderLayout($owner_id, $owner_type, $layout_id, $request);
                    break;
            }
        }
    }
}
if (!$request->isAjax()) {
    $GLOBALS['Response']->redirect($redirect);
}
?>
