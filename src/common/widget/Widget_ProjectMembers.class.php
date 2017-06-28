<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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

require_once('Widget.class.php');
require_once('common/event/EventManager.class.php');

/**
* Widget_ProjectMembers
*/
class Widget_ProjectMembers extends Widget {

    public function __construct()
    {
        parent::__construct('projectmembers');
    }

    public function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','devel_info');
    }
    public function getContent()
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');

        $html = '';

        $res_admin = db_query("SELECT user.user_id AS user_id,user.user_name AS user_name, user.realname as realname "
                            . "FROM user,user_group "
                            . "WHERE user_group.user_id=user.user_id AND user_group.group_id=".db_ei($group_id)." AND "
                            . "user_group.admin_flags = 'A'");
        if (db_numrows($res_admin) > 0) {
            $user_helper = UserHelper::instance();
            $hp          = Codendi_HTMLPurifier::instance();
            $em          = EventManager::instance();
            $html .= '<span class="develtitle">' . $GLOBALS['Language']->getText('include_project_home','proj_admins').':</span><br />';
            while ($row_admin = db_fetch_array($res_admin)) {
                $display_name = '';
                $em->processEvent('get_user_display_name', array(
                          'user_id'           => $row_admin['user_id'],
                          'user_name'         => $row_admin['user_name'],
                          'realname'          => $row_admin['realname'],
                          'user_display_name' => &$display_name
                      ));
                if (!$display_name) {
                    $display_name = $hp->purify($user_helper->getDisplayNameFromUserId($row_admin['user_id']));
                }
                $html .= '<a href="/users/'.$row_admin['user_name'].'/">'. $display_name .'</a><br />';
            }
        }
        $html .= '<span class="develtitle widget-project-team-project-members-title">' . $GLOBALS['Language']->getText('include_project_home','proj_members') . ':</span><br />';
        // count of developers on this project
        $res_count = db_query("SELECT user_id FROM user_group WHERE group_id=".db_ei($group_id));
        $html .= db_numrows($res_count);
        $html .= ' <a href="/project/memberlist.php?group_id=' . $group_id . '">[' . $GLOBALS['Language']->getText('include_project_home','view_members') . ']</a>';

        return $html;
    }

    function getDescription() {
        return $GLOBALS['Language']->getText('widget_description_project_members','description');
    }
}
