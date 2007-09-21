<?php

require_once('pre.php');
require_once('common/widget/Widget.class.php');

$request =& HTTPRequest::instance();
if ($widget =& Widget::getInstance($request->get('name'))) {
    if ($request->get('action') == 'rss') {
        $widget->displayRss();
    } else {
        if ($request->get('action') == 'update') {
            if ($widget->updatePreferences($request)) {
                //hide preferences if all is ok
                $sql = "UPDATE layouts_contents SET display_preferences = 0 WHERE owner_type = 'u' AND owner_id = ". user_getid() ." AND name = '". db_escape_string($request->get('name')) ."'";
                db_query($sql);
                echo db_error();
            }
        }
        if (!$request->isAjax()) {
            $GLOBALS['Response']->redirect('/my/');
        }
    }
}
?>
