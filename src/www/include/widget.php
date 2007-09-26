<?php
/** 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
require_once('common/widget/Widget.class.php');

/**
* widget_widget
* 
* Do operations on widget:
* - display rss
* - update preferences
*
* @param  lm  
* @param  owner_id  
* @param  owner_type  
*/
function widget_widget(&$lm, $owner_id, $owner_type) {
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
}

/**
* widget_updatelayout
* 
* update the layout of widgets (add, remove, minimize, maximize, show preferences, reorder)
*
* @param  lm  WidgetLayoutManager
* @param  owner_id  user_id for /my/ page, group_id for project summary page
* @param  owner_type  OWNER_TYPE_USER / GROUP / HOME
*/
function widget_updatelayout(&$lm, $owner_id, $owner_type) {
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
}

?>
