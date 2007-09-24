<?php
require_once('pre.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('common/widget/Widget.class.php');

$lm = new WidgetLayoutManager();
$owner_id = user_getid();
$owner_type = $lm->OWNER_TYPE_USER;

$request =& HTTPRequest::instance();
if ($layout_id = (int)$request->get('layout_id') || $request->get('action') == 'preferences') {
    $name = null;
    if ($request->exist('name')) {
        $param = $request->get('name');
        $name = array_pop(array_keys($param));
        $instance_id = (int)$param[$name];
    }
    switch($request->get('action')) {
        case 'widget':
            if ($name && $request->exist('layout_id')) {
                if ($widget = Widget::getInstance($name)) {
                    switch($action) {
                        case 'remove':
                            $lm->removeWidget($owner_id, $owner_type, $layout_id, $name, $widget);
                            break;
                        case 'add':
                        default:
                            $lm->addWidget($owner_id, $owner_type, $layout_id, $name, $widget, $request);
                            break;
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
        default:
            $lm->reorderLayout($owner_id, $owner_type, $layout_id, &$request);
            break;
    }
}
if (!$request->isAjax()) {
    $GLOBALS['Response']->redirect('/my/');
}
?>
