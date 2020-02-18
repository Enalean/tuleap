<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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

/**
* Widget_ProjectLatestFileReleases
*
*/
class Widget_ProjectLatestFileReleases extends Widget
{
    public $content;

    public function __construct()
    {
        parent::__construct('projectlatestfilereleases');
        $request = HTTPRequest::instance();
        $pm = ProjectManager::instance();
        $project = $pm->getProject($request->get('group_id'));
        if ($project && $this->canBeUsedByProject($project)) {
            $service = $project->getService(Service::FILE);
            if ($service !== null) {
                assert($service instanceof ServiceFile);
                $this->content = $service->getSummaryPageContent();
            }
        }
    }

    public function getTitle()
    {
        return $this->content['title'];
    }
    public function getContent()
    {
        return $this->content['content'];
    }
    public function isAvailable()
    {
        return isset($this->content['title']);
    }

    private function canBeUsedByProject(Project $project)
    {
        return $project->usesFile();
    }

    public function getCategory()
    {
        return _('Files');
    }
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_project_latest_file_releases', 'description');
    }
}
