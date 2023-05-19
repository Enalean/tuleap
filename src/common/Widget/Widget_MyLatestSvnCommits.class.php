<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
class Widget_MyLatestSvnCommits extends Widget // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * Default number of SVN commits to display (if user did not change/set preferences)
     */
    public const NB_COMMITS_TO_DISPLAY = 5;

    /**
     * Number of SVN commits to display (user preferences)
     *
     * @var int|false
     */
    private $_nb_svn_commits; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

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

    public function _getLinkToCommit($group_id, $commit_id)  // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return '/svn/?func=detailrevision&amp;group_id=' . $group_id . '&amp;rev_id=' . $commit_id;
    }

    public function _getLinkToMore($group_id, $commiter)  // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return '/svn/?func=browse&group_id=' . $group_id . '&_commiter=' . $commiter;
    }

    public function getContent()
    {
        return _('This widget is deprecated, you should remove it.');
    }

    public function hasPreferences($widget_id)
    {
        return false;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        return '
            <div class="tlp-form-element">
                <label class="tlp-label" for="title-' . $widget_id . '">
                    ' . $purifier->purify($GLOBALS['Language']->getText('my_index', 'my_latest_svn_commit_nb_prefs')) . '
                </label>
                <input type="text"
                       size="2"
                       maxlength="3"
                       class="tlp-input"
                       id="title-' . $widget_id . '"
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
        if (! $request->exist('cancel')) {
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
