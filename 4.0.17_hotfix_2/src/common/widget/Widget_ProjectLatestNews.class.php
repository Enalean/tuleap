<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Widget.class.php');

/**
* Widget_ProjectLatestNews
*/
class Widget_ProjectLatestNews extends Widget {
    var $content;
    function Widget_ProjectLatestNews() {
        $this->Widget('projectlatestnews');
        $request =& HTTPRequest::instance();
        $pm = ProjectManager::instance();
        $project = $pm->getProject($request->get('group_id'));
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
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        include('www/export/rss_sfnews.php');
    }
    function canBeUsedByProject(&$project) {
        return $project->usesNews();
    }
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_project_latest_news','description');
    }
}
?>
