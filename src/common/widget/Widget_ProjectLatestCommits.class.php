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
require_once('common/rss/RSS.class.php');

/**
* Widget_ProjectLatestCommits
*/
class Widget_ProjectLatestCommits extends Widget {
    var $latest_revisions = null;
    var $group_id;
    var $commits_callback;

    public function __construct($id, $get_commits_callback)
    {
        parent::__construct($id);

        $request                = HTTPRequest::instance();
        $this->group_id         = $request->get('group_id');
        $this->commits_callback = $get_commits_callback;
    }
    /* protected */ function _getLinkToCommit($data) { }
    /* protected */ function _getLinkToMore() { }

    function getLatestRevisions() {
        if (! $this->latest_revisions) {
            $pm = ProjectManager::instance();
            $project = $pm->getProject($this->group_id);
            if ($project && $this->canBeUsedByProject($project)) {
                $get_commits_callback = $this->commits_callback;
                list($this->latest_revisions,) = $get_commits_callback($project, 0, 5);
            }
        }
        return $this->latest_revisions;
    }

    function getContent() {
        $html = '';
        $i = 1;
        $UH = UserHelper::instance();
        $hp = Codendi_HTMLPurifier::instance();

        $latest_revisions = $this->getLatestRevisions();
        if (! $latest_revisions) {
            return $html;
        }

        while($data = db_fetch_array($latest_revisions)) {
            $html .= '<div class="'. util_get_alt_row_color($i++) .'" style="border-bottom:1px solid #ddd">';
            $html .= '<div style="font-size:0.98em;">';
            $html .= '<a href="'. $this->_getLinkToCommit($data) .'">#'.$data['revision'].'</a>';
            $html .= ' by ';
            if (isset($data['whoid'])) {
                $name = $UH->getDisplayNameFromUserId($data['whoid']);
            } else {
                $name = $UH->getDisplayNameFromUserName($data['who']);
            }
            $html .= $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML).' on ';
            //In the db, svn dates are stored as int whereas cvs dates are stored as timestamp
            $html .= format_date($GLOBALS['Language']->getText('system', 'datefmt'), (is_numeric($data['date']) ? $data['date'] : strtotime($data['date'])));
            $html .= '</div>';
            $html .= '<div style="padding-left:20px; padding-bottom:4px; color:#555">';
            $html .= util_make_links(substr($data['description'], 0, 255), $this->group_id);
            if (strlen($data['description']) > 255) {
                $html .= '&nbsp;[...]';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';
        $html .= '<a href="'. $this->_getLinkToMore() .'">[ More ]</a>';
        $html .= '</div>';
        return $html;
    }
    function isAvailable() {
        return user_isloggedin() ? true : false;
    }
    function hasRss() {
        return false;
    }

    function getCategory() {
        return 'scm';
    }

    function isAjax() {
        return true;
    }

    /*
    Do not use rss for this widget because we don't resolved authentification issue
    function displayRss() {
        $pm = ProjectManager::instance();
        $project = $pm->getProject($this->group_id);
        $rss = new RSS(array(
            'title'       => $project->getPublicName() .' - '. $this->getTitle(),
            'description' => '',
            'link'        => get_server_url(),
            'language'    => 'en-us',
            'copyright'   => $GLOBALS['Language']->getText('rss','copyright',array($GLOBALS['sys_long_org_name'],$GLOBALS['sys_name'],date('Y',time()))),
            'pubDate'     => gmdate('D, d M Y G:i:s',time()).' GMT',
        ));
        while($data = db_fetch_array($this->latest_revisions)) {
            $rss->addItem(array(
                'title'       => '#'.$data['revision'] .' by '. $data['who'] .' on '. format_date($GLOBALS['Language']->getText('system', 'datefmt'), $data['date']),
                'description' => util_make_links(nl2br($data['description'])),
                'link'        => '/svn/?func=detailrevision&amp;group_id='.$this->group_id.'&amp;commit_id='.$data['commit_id']
            ));
        }
        $rss->display();
        exit;

    }*/
}
?>
