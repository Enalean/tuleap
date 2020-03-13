<?php
/**
 * Copyright (c) Enalean, 2013-2019. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Laurent Julliard 2004 Codendi Team, Xerox
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

require_once('svn_utils.php');

function svn_data_get_technicians($group_id)
{
    // Get list of all people who once committed something in the CVS
    // including those who may have been removed from the project since then.
    $sql = "SELECT DISTINCT user.user_name, user.user_name" .
        " FROM user INNER JOIN svn_commits ON (svn_commits.whoid=user.user_id)" .
        " WHERE svn_commits.group_id=" . db_ei($group_id) .
        " ORDER BY user.user_name ASC";
    return db_query($sql);
}

function svn_data_update_general_settings($group_id, $svn_tracked, $svn_preamble, $svn_mandatory_ref, $svn_can_change_log)
{
    $query = "update groups set svn_tracker='" . db_ei($svn_tracked) .
    "', svn_preamble='" . db_es(htmlspecialchars($svn_preamble)) .
        "', svn_mandatory_ref='" . db_ei($svn_mandatory_ref) .
        "', svn_can_change_log='" . db_ei($svn_can_change_log) .
    "' where group_id='" . db_ei($group_id) . "'";
    $result = db_query($query);
    return ($result ? true : false);
}

// list the number of commits by user either since the beginning of
// history if the period argument is not given or if it is given then
// over the last "period" of time.
// period is expressed in seconds
function svn_data_get_svn_history($group_id, $period = false)
{
    $date_clause = '';
    if ($period) {
    // All times in svn tables are stored in UTC!!!
        $date_clause = " AND date >= " . db_ei((gmdate('U') - $period)) . " ";
    }
    $query = "SELECT whoid, user.user_name, count(id) as commits " .
    "FROM svn_commits, user " .
    "WHERE svn_commits.whoid=user.user_id AND svn_commits.group_id=" . db_ei($group_id) . " " .
    $date_clause .
    "GROUP BY whoid ORDER BY user_name";
    $result = db_query($query);
    return($result);
}

function svn_data_get_revision_detail($group_id, $commit_id, $rev_id = 0, $order = '')
{
    $order_str = "";
    if ($order) {
        if ($order != 'filename') {
            // SQLi Warning: no real possibility to escape $order here.
            // We rely on a proper filtering of user input by calling methods.
            $order_str = " ORDER BY " . $order;
        } else {
            $order_str = " ORDER BY dir, file";
        }
    }

    //check user access rights
    $pm = ProjectManager::instance();
    $project = $pm->getProject($group_id);

    $forbidden = svn_utils_get_forbidden_paths(user_getname(), $project->getSVNRootPath());
    $where_forbidden = "";
    if (!empty($forbidden)) {
        foreach ($forbidden as $no_access => $value) {
            $where_forbidden .= " AND svn_dirs.dir not like '%" . db_es(substr($no_access, 1)) . "%' ";
        }
    }

    // if the subversion revision id is given then it akes precedence on
    // the internal commit_id (this is to make it easy for users to build
    // URL to access a revision
    if ($rev_id) {
    // To be done -> get the commit ID from the svn-commit table
        $sql = "SELECT svn_commits.description, svn_commits.date, svn_commits.revision, svn_checkins.type,svn_checkins.commitid,svn_dirs.dir,svn_files.file " .
        "FROM svn_dirs, svn_files, svn_checkins, svn_commits " .
        "WHERE svn_checkins.fileid=svn_files.id " .
        "AND svn_checkins.dirid=svn_dirs.id " .
        "AND svn_checkins.commitid=svn_commits.id " .
        "AND svn_commits.revision=" . db_ei($rev_id) . " " .
        "AND svn_commits.group_id=" . db_ei($group_id) . " " .
        $where_forbidden . $order_str;
    } else {
        $sql = "SELECT svn_commits.description, svn_commits.date, svn_commits.revision, svn_checkins.type,svn_checkins.commitid,svn_dirs.dir,svn_files.file " .
        "FROM svn_dirs, svn_files, svn_checkins, svn_commits " .
        "WHERE svn_checkins.fileid=svn_files.id " .
        "AND svn_checkins.dirid=svn_dirs.id " .
        "AND svn_checkins.commitid=svn_commits.id " .
        "AND svn_commits.id=" . db_ei($commit_id) . " " .
        $where_forbidden . $order_str;
    }

    $result = db_query($sql);
    return $result;
}
