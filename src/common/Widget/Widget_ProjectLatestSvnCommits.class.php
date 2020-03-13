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

require_once __DIR__ . '/../../www/svn/svn_utils.php';

/**
* Widget_ProjectLatestSvnCommits
*/
class Widget_ProjectLatestSvnCommits extends Widget_ProjectLatestCommits
{

    public function __construct()
    {
        parent::__construct('projectlatestsvncommits', 'svn_get_revisions');
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('include_project_home', 'latest_svn_commit');
    }
    public function _getLinkToCommit($data)
    {
        return '/svn/?func=detailrevision&amp;group_id=' . $this->group_id . '&amp;commit_id=' . $data['commit_id'];
    }
    public function _getLinkToMore()
    {
        return '/svn/?func=browse&group_id=' . $this->group_id;
    }

    protected function canBeUsedByProject(Project $project)
    {
        return $project->usesSvn();
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_project_latest_svn_commits', 'description');
    }

    public function getLatestRevisions()
    {
        if (! $this->latest_revisions) {
            $pm = ProjectManager::instance();
            $project = $pm->getProject($this->group_id);
            if ($project && $this->canBeUsedByProject($project)) {
                list($this->latest_revisions,) = svn_get_revisions($project, 0, 5, '', '', '', '', 0, false);
            }
        }
        return $this->latest_revisions;
    }
}
