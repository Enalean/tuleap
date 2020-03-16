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

require_once __DIR__ . '/../../www/cvs/commit_utils.php';

/**
* Widget_ProjectLatestCvsCommits
*
*/
class Widget_ProjectLatestCvsCommits extends Widget_ProjectLatestCommits
{

    public function __construct()
    {
        parent::__construct('projectlatestcvscommits', 'cvs_get_revisions');
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('include_project_home', 'latest_cvs_commit');
    }
    public function _getLinkToCommit($data)
    {
        return '/cvs/index.php?func=detailcommit&amp;group_id=' . $this->group_id . '&amp;commit_id=' . $data['id'];
    }
    public function _getLinkToMore()
    {
        return '/cvs/?func=browse&group_id=' . $this->group_id;
    }

    protected function canBeUsedByProject(Project $project)
    {
        return $project->usesCvs();
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_project_latest_cvs_commits', 'description');
    }
}
