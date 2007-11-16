<?php

require_once('pre.php');
require_once('common/widget/Widget.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');

$lm = new WidgetLayoutManager();

$request =& HTTPRequest::instance();
$good = false;
$redirect   = '/';
$owner = $request->get('owner');
if ($owner) {
    $owner_id   = (int)substr($owner, 1);
    $owner_type = substr($owner, 0, 1);
    switch($owner_type) {
        case $lm->OWNER_TYPE_USER:
            $owner_id = user_getid();
            $redirect = '/my/';
            $good = true;
            break;
        case $lm->OWNER_TYPE_GROUP:
            if ($project = project_get_object($owner_id)) {
                $group_id = $owner_id;
                $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
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
            if ($widget =& Widget::getInstance($name)) {
                if ($widget->isAvailable()) {
                    if ($request->get('action') == 'rss') {
                        $widget->displayRss();
                        exit();
                    } else {
                        if ($request->get('action') == 'update' && ($layout_id = (int)$request->get('layout_id'))) {
                            if ($owner_type == $lm->OWNER_TYPE_USER || user_ismember($group_id, 'A') || user_is_super_user()) {
                                if ($request->get('cancel') || $widget->updatePreferences($request)) {
                                    $lm->hideWidgetPreferences($owner_id, $owner_type, $layout_id, $name, $instance_id);
                                }
                            }
                        }
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
