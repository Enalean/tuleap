<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

require_once('Widget.class.php');

/**
* Widget_ProjectLatestFileReleases
* 
*/
class Widget_ProjectLatestFileReleases extends Widget {
    var $content;

    public function __construct()
    {
        parent::__construct('projectlatestfilereleases');
        $request = HTTPRequest::instance();
        $pm = ProjectManager::instance();
        $project = $pm->getProject($request->get('group_id'));
        if ($project && $this->canBeUsedByProject($project)) {
            $this->content = $project->getService(Service::FILE)->getSummaryPageContent();
        }
    }

    function getTitle() {
        return $this->content['title'];
    }
    function getContent() {
        return $this->content['content'];
    }
    function isAvailable() {
        return isset($this->content['title']);
    }
    function canBeUsedByProject(&$project) {
        return $project->usesFile();
    }
    
    function getCategory() {
        return 'frs';
    }
    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_project_latest_file_releases','description');
    }
}
?>