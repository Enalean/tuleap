<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
* Widget_MyAdmin
*
* Personal Admin
*/
class Widget_MyAdmin extends Widget
{
    private $user_is_super_admin;

    public function __construct($user_is_super_admin)
    {
        parent::__construct('myadmin');
        $this->user_is_super_admin = $user_is_super_admin;
    }

    public function getTitle()
    {
        if ($this->user_is_super_admin) {
            return $GLOBALS['Language']->getText('my_index', 'my_admin');
        } else {
            return $GLOBALS['Language']->getText('my_index', 'my_admin_non_super');
        }
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('my_index', 'my_admin_description');
    }

    public function getContent()
    {
        $html_my_admin = '<table width="100%" class="tlp-table">';

        if ($this->user_is_super_admin) {
            $html_my_admin .= $this->getHTMLForSuperAdmin();
        } else {
            $row_colour_id = 0;
            $html_my_admin .= $this->getHTMLForNonSuperAdmin($row_colour_id);
        }

        $html_my_admin .= '</table>';

        return $html_my_admin;
    }

    private function getHTMLForSuperAdmin()
    {
        require_once __DIR__ . '/../../www/forum/forum_utils.php';
        require_once __DIR__ . '/../../www/project/admin/ugroup_utils.php';

        $html_my_admin = '';
        // Get the number of pending users and projects

        if ($GLOBALS['sys_user_approval'] == 1) {
            db_query("SELECT count(*) AS count FROM user WHERE status='P'");
            $row = db_fetch_array();
            $pending_users = $row['count'];
        } else {
            db_query("SELECT count(*) AS count FROM user WHERE status='P' OR status='V' OR status='W'");
            $row = db_fetch_array();
            $pending_users = $row['count'];
        }
        db_query("SELECT count(*) AS count FROM user WHERE status='V' OR status='W'");
        $row = db_fetch_array();
        $validated_users = $row['count'];

        $sql = "SELECT * FROM news_bytes WHERE is_approved=0 OR is_approved=3";
        $result = db_query($sql);
        $pending_news = 0;
        $rows = db_numrows($result);
        for ($i = 0; $i < $rows; $i++) {
            //if the news is private, not display it in the list of news to be approved
            $forum_id = db_result($result, $i, 'forum_id');
            $res = news_read_permissions($forum_id);
            // check on db_result($res,0,'ugroup_id') == $UGROUP_ANONYMOUS only to be consistent
            // with ST DB state
            if ((db_numrows($res) < 1) || (db_result($res, 0, 'ugroup_id') == $GLOBALS['UGROUP_ANONYMOUS'])) {
                $pending_news++;
            }
        }

        $i = 0;
        $html_my_admin .= $this->_get_admin_row(
            $i++,
            $GLOBALS['Language']->getText('admin_main', 'pending_user', array("/admin/approve_pending_users.php?page=pending")),
            $pending_users,
            $this->_get_color($pending_users)
        );

        if ($GLOBALS['sys_user_approval'] == 1) {
            $html_my_admin .= $this->_get_admin_row(
                $i++,
                $GLOBALS['Language']->getText('admin_main', 'validated_user', array("/admin/approve_pending_users.php?page=validated")),
                $validated_users,
                $this->_get_color($validated_users)
            );
        }

        $html_my_admin .= $this->getHTMLForNonSuperAdmin($i);

        $html_my_admin .= $this->_get_admin_row(
            $i++,
            '<a href="/admin/news/">' . $GLOBALS['Language']->getText('admin_main', 'site_news_approval') . '</a>',
            $pending_news,
            $this->_get_color($pending_news)
        );

        $pendings = array();
        $em = EventManager::instance();
        $em->processEvent('widget_myadmin', array('result' => &$pendings));
        foreach ($pendings as $entry) {
            $html_my_admin .= $this->_get_admin_row(
                $i++,
                $entry['text'],
                $entry['value'],
                $entry['bgcolor'],
                isset($entry['textcolor']) ? $entry['textcolor'] : 'white'
            );
        }

        return $html_my_admin;
    }

    private function getHTMLForNonSuperAdmin($i)
    {
        db_query("SELECT count(*) AS count FROM groups WHERE status='P'");
        $row = db_fetch_array();
        $pending_projects = $row['count'];

        return $this->_get_admin_row(
            $i++,
            $GLOBALS['Language']->getText('admin_main', 'pending_group', array("/admin/approve-pending.php")),
            $pending_projects,
            $this->_get_color($pending_projects)
        );
    }

    public function _get_color($nb)
    {
        return $nb == 0 ? 'green' : 'orange';
    }

    public function _get_admin_row($i, $text, $value, $bgcolor, $textcolor = 'white')
    {
        return '<tr class="' . util_get_alt_row_color($i++) . '"><td>' . $text . '</td><td nowrap="nowrap" style="width:20%; background:' . $bgcolor . '; color:' . $textcolor . '; padding: 2px 8px; font-weight:bold; text-align:center;">' . $value . '</td></tr>';
    }
}
