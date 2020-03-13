<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class GroupFactory
{
    /**
     * @var bool
     */
    private $error_state = false;

    /**
     *
     *
     *    @return bool success.
     */
    public function __construct()
    {
            return true;
    }

    /**
     *    return a resultset of Group
     *
     *    @return    resultset
     */
    public function getAllGroups()
    {
        global $Language;
        if (user_isloggedin()) {
            // For  surperuser), we can see all the trackers (both public and non public)
            if (user_is_super_user()) {
                $access_condition = '';
            } else {
                $access_condition = " AND access NOT IN ('" .
                    db_es(Project::ACCESS_PRIVATE) . "', '" .
                    db_es(Project::ACCESS_PRIVATE_WO_RESTRICTED) . "')";
            }
        } else {
            if (isset($GLOBALS['Language'])) {
                $this->setError();
            }

            return false;
        }

        $sql = "SELECT group_id,group_name,unix_group_name FROM groups
			WHERE group_id <> 100 AND status = 'A'
			$access_condition
			ORDER BY group_name ASC";

     //echo $sql;

        $result = db_query($sql);

        $rows = db_numrows($result);

        if (!$result || $rows < 1) {
            if (isset($GLOBALS['Language'])) {
                $this->setError();
            }
                    return false;
        }
        return $result;
    }

    /**
     *    return a resultset of Group for the current user
     *
     *    @return    resultset
     */
    public function getMemberGroups()
    {
                global $Language;
        if (!user_isloggedin()) {
            $this->setError();
            return false;
        }

        $db_escaped_user_id = db_ei(UserManager::instance()->getCurrentUser()->getId());

        $sql = "SELECT g.group_id,g.group_name " .
        "FROM groups g, user_group ug " .
        "WHERE g.group_id <> 100 AND g.status = 'A' AND g.group_id = ug.group_id " .
        "AND ug.user_id=" . $db_escaped_user_id . " " .
        "ORDER BY g.group_name ASC";

     //echo $sql;

        $result = db_query($sql);

        $rows = db_numrows($result);

        if (!$result || $rows < 1) {
            $this->setError();
            return false;
        }
        return $result;
    }

    /**
     *    return an array of Group for the current user (the groups the user is member of)
     *
     *    @return    array of {Group}
     */
    public function getMyGroups()
    {
        global $Language;
        $result_my_groups = $this->getMemberGroups();
        if ($this->isError() || !$result_my_groups) {
            return false;
        } else {
            $pm = ProjectManager::instance();
            $my_groups = array();
            while ($res_group = db_fetch_array($result_my_groups)) {
                $group = $pm->getProject($res_group['group_id']);
                if ($group && !$group->isError()) {
                    $my_groups[$group->getID()] = $group;
                }
            }
            return $my_groups;
        }
    }

    /**
     * @internal param $string
     */
    public function setError()
    {
        $this->error_state = true;
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return $this->error_state;
    }
}
