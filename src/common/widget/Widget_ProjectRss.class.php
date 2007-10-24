<?php

require_once('Widget_Rss.class.php');
require_once('WidgetLayoutManager.class.php');

/**
* Widget_ProjectRss
* 
* Project rss reader
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_ProjectRss extends Widget_Rss {
    function Widget_ProjectRss() {
        $lm = new WidgetLayoutManager();
        $request =& HTTPRequest::instance();
        $this->Widget_Rss('projectrss', $request->get('group_id'), $lm->OWNER_TYPE_GROUP);
    }
    function canBeUsedByProject(&$project) {
        return true;
    }
}
?>
