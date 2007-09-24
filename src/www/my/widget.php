<?php

require_once('pre.php');
require_once('common/widget/Widget.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');

$lm = new WidgetLayoutManager();
$owner_id = user_getid();
$owner_type = $lm->OWNER_TYPE_USER;

$request =& HTTPRequest::instance();
if ($request->exist('name')) {
    $param = $request->get('name');
    $name = array_pop(array_keys($param));
    $instance_id = (int)$param[$name];
    if ($widget =& Widget::getInstance($name)) {
        if ($request->get('action') == 'rss') {
            $widget->displayRss();
        } else {
            if ($request->get('action') == 'update' && ($layout_id = (int)$request->get('layout_id'))) {
                if ($widget->updatePreferences($request)) {
                    $lm->hideWidgetPreferences($owner_id, $owner_type, $layout_id, $name, $instance_id);
                }
            }
            if (!$request->isAjax()) {
                $GLOBALS['Response']->redirect('/my/');
            }
        }
    }
}

?>
