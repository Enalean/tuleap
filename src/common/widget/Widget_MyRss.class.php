<?php

require_once('Widget_Rss.class.php');
require_once('WidgetLayoutManager.class.php');

/**
* Widget_MyRss
* 
* Personal rss reader
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_MyRss extends Widget_Rss {
    function Widget_MyRss() {
        $this->Widget_Rss('myrss', user_getid(), WidgetLayoutManager::OWNER_TYPE_USER);
    }
}
?>
