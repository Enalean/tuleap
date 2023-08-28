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
* Widget_ProjectLatestCommits
*/
abstract class Widget_ProjectLatestCommits extends Widget
{
    public $latest_revisions = null;
    public $group_id;
    public $commits_callback;

    public function __construct($id, $get_commits_callback)
    {
        parent::__construct($id);

        $request                = HTTPRequest::instance();
        $this->group_id         = $request->get('group_id');
        $this->commits_callback = $get_commits_callback;
    }

    /* protected */ public function _getLinkToCommit($data)
    {
    }

    /* protected */ public function _getLinkToMore()
    {
    }

    abstract protected function canBeUsedByProject(Project $project);

    public function getLatestRevisions()
    {
        if (! $this->latest_revisions) {
            $pm      = ProjectManager::instance();
            $project = $pm->getProject($this->group_id);
            if ($project && $this->canBeUsedByProject($project)) {
                $get_commits_callback          = $this->commits_callback;
                list($this->latest_revisions,) = $get_commits_callback($project, 0, 5);
            }
        }

        return $this->latest_revisions;
    }

    public function getContent()
    {
        $html = '';
        $i    = 1;
        $UH   = UserHelper::instance();
        $hp   = Codendi_HTMLPurifier::instance();

        $latest_revisions = $this->getLatestRevisions();
        if (! $latest_revisions) {
            return $html;
        }

        $number_of_commit = 0;
        while ($data = db_fetch_array($latest_revisions)) {
            $html .= '<div class="' . util_get_alt_row_color($i++) . '" style="border-bottom:1px solid #ddd">';
            $html .= '<div style="font-size:0.98em;" class="project-last-commit-text">';
            $html .= '<a href="' . $this->_getLinkToCommit($data) . '">#' . $data['revision'] . '</a>';
            $html .= ' by ';
            if (isset($data['whoid'])) {
                $name = $UH->getDisplayNameFromUserId($data['whoid']);
            } else {
                $name = $UH->getDisplayNameFromUserName($data['who']);
            }
            $html .= $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) . ' on ';
            //In the db, svn dates are stored as int
            $html .= format_date(
                $GLOBALS['Language']->getText('system', 'datefmt'),
                (is_numeric($data['date']) ? $data['date'] : strtotime($data['date']))
            );
            $html .= '</div>';
            $html .= '<div style="padding-left:20px; padding-bottom:4px; color:#555">';
            $html .= $hp->purify(substr($data['description'], 0, 255), CODENDI_PURIFIER_BASIC_NOBR, $this->group_id);
            if (strlen($data['description']) > 255) {
                $html .= '&nbsp;[...]';
            }
            $html .= '</div>';
            $html .= '</div>';

            $number_of_commit++;
        }

        if ($number_of_commit === 0) {
            $html .= $GLOBALS['Language']->getText('my_index', 'my_latest_commit_empty');

            return $html;
        }

        $html .= '<div class="' . util_get_alt_row_color($i++) . ' project-last-commit-text-more">';
        $html .= '<a href="' . $this->_getLinkToMore() . '">[ More ]</a>';
        $html .= '</div>';

        return $html;
    }

    public function isAvailable()
    {
        return user_isloggedin() ? true : false;
    }

    public function getCategory()
    {
        return _('Source code management');
    }

    public function isAjax()
    {
        return true;
    }
}
