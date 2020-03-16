<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi 2001-2009.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../../www/svn/svn_utils.php';

/**
* Widget_MyLatestSvnCommits
*/
class Widget_MyLatestSvnCommits extends Widget
{

    /**
     * Default number of SVN commits to display (if user did not change/set preferences)
     */
    public const NB_COMMITS_TO_DISPLAY = 5;

    /**
     * Number of SVN commits to display (user preferences)
     */
    private $_nb_svn_commits;

    public function __construct()
    {
        parent::__construct('mylatestsvncommits');
        $this->_nb_svn_commits = user_get_preference('my_latests_svn_commits_nb_display');
        if ($this->_nb_svn_commits === false) {
            $this->_nb_svn_commits = self::NB_COMMITS_TO_DISPLAY;
            user_set_preference('my_latests_svn_commits_nb_display', $this->_nb_svn_commits);
        }
    }

    public function getTitle()
    {
        return $GLOBALS['Language']->getText('my_index', 'my_latest_svn_commit');
    }
    public function _getLinkToCommit($group_id, $commit_id)
    {
        return '/svn/?func=detailrevision&amp;group_id=' . $group_id . '&amp;rev_id=' . $commit_id;
    }
    public function _getLinkToMore($group_id, $commiter)
    {
        return '/svn/?func=browse&group_id=' . $group_id . '&_commiter=' . $commiter;
    }

    public function getContent()
    {
        $html        = '';
        $uh          = UserHelper::instance();
        $request     = HTTPRequest::instance();
        $hp          = Codendi_HTMLPurifier::instance();
        $user        = UserManager::instance()->getCurrentUser();
        $pm          = ProjectManager::instance();
        $project_ids = $user->getProjects();

        $revision_total = 0;
        foreach ($project_ids as $project_id) {
            $project = $pm->getProject($project_id);
            if ($project->usesSVN()) {
                $html .= '<div class="project-last-commit-project-title">';
                list($hide_now,$count_diff,$hide_url) = my_hide_url('my_svn_group', $project_id, $request->get('hide_item_id'), count($project_ids), $request->get('hide_my_svn_group'), $request->get('dashboard_id'));
                $html .= $hide_url;

                $html .= '<strong>' . $hp->purify($project->getPublicName()) . '</strong>';
                if (!$hide_now) {
                    list($latest_revisions, $nb_revisions) = svn_get_revisions($project, 0, $this->_nb_svn_commits, '', $user->getUserName(), '', '', 0, false);
                    $revision_total += $nb_revisions;
                    if (db_numrows($latest_revisions) > 0) {
                        $i = 0;
                        while ($data = db_fetch_array($latest_revisions)) {
                            $html .= '<div class="' . util_get_alt_row_color($i++) . '" style="border-bottom:1px solid #ddd">';
                            $html .= '<div style="font-size:0.98em;" class="project-last-commit-text">';
                            $html .= '<a href="' . $this->_getLinkToCommit($project->getGroupId(), $data['revision']) . '">rev #' . $data['revision'] . '</a>';
                            $html .= ' ' . $GLOBALS['Language']->getText('my_index', 'my_latest_svn_commit_on') . ' ';
                            //In the db, svn dates are stored as int whereas cvs dates are stored as timestamp
                            $html .= format_date($GLOBALS['Language']->getText('system', 'datefmt'), (is_numeric($data['date']) ? $data['date'] : strtotime($data['date'])));
                            $html .= ' ' . $GLOBALS['Language']->getText('my_index', 'my_latest_svn_commit_by') . ' ';
                            if (isset($data['whoid'])) {
                                $name = $uh->getDisplayNameFromUserId($data['whoid']);
                            } else {
                                $name = $uh->getDisplayNameFromUserName($data['who']);
                            }
                            $html .= $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML);
                            $html .= '</div>';
                            $html .= '<div style="padding-left:20px; padding-bottom:4px; color:#555">';
                            $html .= $hp->purify(substr($data['description'], 0, 255), CODENDI_PURIFIER_BASIC_NOBR, $project->getGroupId());
                            if (strlen($data['description']) > 255) {
                                $html .= '&nbsp;[...]';
                            }
                            $html .= '</div>';
                            $html .= '</div>';
                        }
                        $html .= '<div style="text-align:center" class="' . util_get_alt_row_color($i++) . ' project-last-commit-text-more">';
                        $html .= '<a href="' . $this->_getLinkToMore($project->getGroupId(), $user->getUserName()) . '">[ More ]</a>';
                        $html .= '</div>';
                    } else {
                        $html .= '<div>' .
                            $GLOBALS['Language']->getText('my_index', 'my_latest_commit_empty') . '</div>';
                    }
                } else {
                    $html .= '<div></div>';
                }

                $html .=  '</div>';
            }
        }

        if ($revision_total === 0) {
            $html .= $GLOBALS['Language']->getText('my_index', 'my_latest_commit_empty');
        }
        return $html;
    }

    public function hasPreferences($widget_id)
    {
        return true;
    }

    public function getPreferences($widget_id)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="title-' . (int) $widget_id . '">
                    ' . $purifier->purify($GLOBALS['Language']->getText('my_index', 'my_latest_svn_commit_nb_prefs')) . '
                </label>
                <input type="text"
                       size="2"
                       maxlength="3"
                       class="tlp-input"
                       id="title-' . (int) $widget_id . '"
                       name="nb_svn_commits"
                       value="' . $purifier->purify(user_get_preference('my_latests_svn_commits_nb_display')) . '"
                       placeholder="' . $purifier->purify(self::NB_COMMITS_TO_DISPLAY) . '">
            </div>
            ';
    }

    public function updatePreferences(Codendi_Request $request)
    {
        $request->valid(new Valid_String('cancel'));
        $nbShow = new Valid_UInt('nb_svn_commits');
        $nbShow->required();
        if (!$request->exist('cancel')) {
            if ($request->valid($nbShow)) {
                $this->_nb_svn_commits = $request->get('nb_svn_commits');
            } else {
                $this->_nb_svn_commits = self::NB_COMMITS_TO_DISPLAY;
            }
            user_set_preference('my_latests_svn_commits_nb_display', $this->_nb_svn_commits);
        }
        return true;
    }

    public function getCategory()
    {
        return _('Source code management');
    }
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_my_latest_svn_commits', 'description');
    }
    public function isAjax()
    {
        return true;
    }

    public function getAjaxUrl($owner_id, $owner_type, $dashboard_id)
    {
        $request  = HTTPRequest::instance();
        $ajax_url = parent::getAjaxUrl($owner_id, $owner_type, $dashboard_id);
        if ($request->exist('hide_item_id') || $request->exist('hide_my_svn_group')) {
            $ajax_url .= '&hide_item_id=' . urlencode($request->get('hide_item_id')) .
                '&hide_my_svn_group=' . urlencode($request->get('hide_my_svn_group'));
        }

        return $ajax_url;
    }
}
