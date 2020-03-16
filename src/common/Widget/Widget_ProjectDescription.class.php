<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

/**
* Widget_ProjectDescription
*
*/
class Widget_ProjectDescription extends Widget
{

    public function __construct()
    {
        parent::__construct('projectdescription');
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('include_project_home', 'project_description');
    }
    public function getContent()
    {
        $request = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        $hp = Codendi_HTMLPurifier::instance();

        $html = '';

        if ($project->getStatus() == 'H') {
            $html .= '<p style="font-size:1.4em;">' . $GLOBALS['Language']->getText('include_project_home', 'not_official_site', $GLOBALS['sys_name']) . '</p>';
        }

        if ($project->getDescription()) {
            $html .= '<p style="font-size:1.4em;">' . $hp->purify($project->getDescription(), CODENDI_PURIFIER_LIGHT, $group_id) . "</p>";
            $details_prompt = '[' . $GLOBALS['Language']->getText('include_project_home', 'more_info') . '...]';
        } else {
            $html .= '<p>' . $GLOBALS['Language']->getText('include_project_home', 'no_short_desc', "/project/admin/editgroupinfo.php?group_id=$group_id") . '</p>';
            $details_prompt = '[' . $GLOBALS['Language']->getText('include_project_home', 'other_info') . '...]';
        }

        $html .= '<a href="/project/showdetails.php?group_id=' . $group_id . '"> ' . $details_prompt . '</a>';

        return $html;
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_project_description', 'description');
    }
}
