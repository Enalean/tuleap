<?php

require_once('Widget.class.php');

/**
* Widget_ProjectLatestNews
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Widget_ProjectLatestNews extends Widget {
    var $content;
    function Widget_ProjectLatestNews() {
        $this->Widget('projectlatestnews');
        $request =& HTTPRequest::instance();
        $project =& project_get_object($request->get('group_id'));
        if ($project && $this->canBeUsedByProject($project)) {
            require_once('www/news/news_utils.php');
            $this->content = news_show_latest($request->get('group_id'),10,false);
        }
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','latest_news');
    }
    function getContent() {
        return $this->content;
    }
    function isAvailable() {
        return $this->content ? true : false;
    }
    function hasRss() {
        return true;
    }
    function displayRss() {
        global $Language;
        include('www/export/rss_sfnews.php');
    }
    function canBeUsedByProject(&$project) {
        return $project->usesNews();
    }
}
?>
