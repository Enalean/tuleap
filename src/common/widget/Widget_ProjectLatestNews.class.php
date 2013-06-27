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

    /**
     * Constructor of the class
     *
     * @retun Void
     */
    function Widget_ProjectLatestNews() {
        $this->Widget('projectlatestnews');
        $request = $this->getHTTPRequest();
        $pm      = $this->getProjectManager();
        $project = $pm->getProject($request->get('group_id'));
        if ($project && $this->canBeUsedByProject($project)) {
            require_once('www/news/news_utils.php');
            $this->content = news_show_latest($request->get('group_id'), 10, false);
        }
    }

    /**
     * Title of the widget
     *
     * @return String
     */
    function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home', 'latest_news');
    }

    /**
     * Content of the widget
     *
     * @return String
     */
    function getContent() {
        return $this->content;
    }

    /**
     * Content is available
     *
     * @return Boolean
     */
    function isAvailable() {
        return $this->content ? true : false;
    }

    /**
     * Allow RSS display
     *
     * @return Boolean
     */
    public function hasRss() {
        return true;
    }

    /**
     * Display RSS
     *
     * @return Void
     */
    function displayRss() {
        global $Language;
        $request  = $this->getHTTPRequest();
        $group_id = $request->get('group_id');
        include('www/export/rss_sfnews.php');
    }

    /**
     * Does project has news
     *
     * @param Project $project The project
     *
     * @return Boolean
     */
    function canBeUsedByProject($project) {
        return $project->usesNews();
    }

    /**
     * Description of the widget
     *
     * @return String
     */
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_project_latest_news', 'description');
    }

    /**
     * HTTPRequest instance
     *
     * @return HTTPRequest
     */
    private function getHTTPRequest() {
        return HTTPRequest::instance();
    }

    /**
     * ProjectManager instance
     *
     * @return ProjectManager
     */
    private function getProjectManager() {
        return ProjectManager::instance();
    }

}

?>