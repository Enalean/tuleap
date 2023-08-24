<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

/**
 * Prepare SQL query for given date and given person
 */
function logs_cond($project, int $span, $who)
{
    // Get information about the date $span days ago
    // Start at midnight $span days ago
    $time_back = localtime((time() - ($span * 86400)), 1);

    // This for debug
    // print "time_back= ". $time_back['tm_hour'].":".$time_back['tm_min'].":".$time_back['tm_sec']." on ".$time_back['tm_mday']." ".$time_back['tm_mon']." ".$time_back['tm_year']."<BR>";

    // Adjust to midnight this day
    $time_back["tm_sec"] = $time_back["tm_min"] = $time_back["tm_hour"] = 0;
    $begin_date          = mktime($time_back["tm_hour"], $time_back["tm_min"], $time_back["tm_sec"], $time_back["tm_mon"] + 1, $time_back["tm_mday"], $time_back["tm_year"] + 1900);

    // For Debug
    // print join(" ",localtime($begin_date,0))."<BR>";
    // print "begin_date: $begin_date<BR>";

    if ($who == "allusers") {
        $cond = "";
    } else {
        $users = implode(',', $project->getMembersId());
        if ($who == "members") {
            $cond = " AND user.user_id IN ($users) ";
        } else {
            $cond = " AND user.user_id NOT IN ($users) ";
        }
    }

    $whereclause = "log.user_id=user.user_id " . $cond
    . " AND log.time >= $begin_date ";

    return $whereclause;
}

/**
 * Process SQL query and display corresponding result
 */
function logs_display($sql, int $span, $field, $title = '')
{
    $hp = Codendi_HTMLPurifier::instance();
    // Executions will continue until morale improves.
    $res = db_query($sql);

    print '<p><u><b>' . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'access_for_past_x_days', [$hp->purify($title), $hp->purify($span)]);
    if (($nb_downloads = db_numrows($res)) >= 1) {
        $row = db_fetch_array($res);
        print ' - ' . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'in_total', $hp->purify($nb_downloads)) . '</u></b>';

        print '<table width="100%" cellpadding="2" cellspacing="0" border="0">' . "\n"
            . '<tr valign="top">' . "\n"
            . ' <th>' . $GLOBALS['Language']->getText('project_admin_utils', 'date') . '</th>' . "\n";

        if (isset($row['type'])) {
            print ' <th>' . $GLOBALS['Language']->getText('project_admin_utils', 'action') . '</th>' . "\n";
        }
        print ' <th>' . $GLOBALS['Language']->getText('project_export_utils', 'user') . '</th>' . "\n"
            . ' <th>' . $GLOBALS['Language']->getText('project_export_artifact_history_export', 'email') . '</th>' . "\n"
            . ' <th>' . $hp->purify($field) . '</th>' . "\n"
            . ' <th align="right">' . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'time') . '</th>' . "\n"
            . '</tr>' . "\n";
        $i = 0;
        do {
            print '<tr class="' . util_get_alt_row_color($i++) . '">'
            . ' <td>' . (string) date("j M Y", $row['time']) . '</td>';
            if (isset($row['type'])) {
                print ' <td>' . $hp->purify($row['type']) . '</td>';
            }

            print ' <td> <a href="/users/' . $hp->purify($row["user_name"]) . '/">' . $hp->purify($row["user_name"]) . '</a> (' . $hp->purify($row["realname"]) . ')</td>'
                . ' <td>' . $hp->purify($row["email"]) . '</td>';
            print ' <td>';
            print $hp->purify($row["title"], CODENDI_PURIFIER_CONVERT_HTML) . '</td>';

            if (isset($row['local_time'])) {
                print ' <td align="right">' . $hp->purify($row['local_time']) . '</td>';
            } else {
                print ' <td align="right">' . (string) date("H:i", $row["time"]) . '</td>';
            }

            print '</tr>' . "\n";
        } while ($row = db_fetch_array($res));

        print '</table>';
    } else {
        echo "</u></b>
        <p>" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'no_access') . "</p>";
    }
}

function frs_logs_extract($project, int $span, $who)
{
    /*
     * This request is used to obtain FRS actions log such as package, release or file : creation, update or deletion.
     * Each SELECT statement is used to obtain logs related to an FRS element type.
     *    SELECT #1 : Creation, update and deletion of packages.
     *    SELECT #2 : Creation, update and deletion of releases.
     *    SELECT #3 : Creation, update and deletion of files.
     *    SELECT #4 : Restoration of files.
     * Each CASE statement is used to replace log.action_id by text description corresponding to the action.
     * So don't worry if this request seem so big and so hard to understand in fact it's a relatively simple union of selects.
     */
    $sql = "    SELECT log.log_id, log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, frs_package.name AS title," .
           "        CASE " .
           "        WHEN log.action_id = " . FRSPackage::EVT_CREATE . " THEN '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_create_package') . "'" .
           "        WHEN log.action_id = " . FRSPackage::EVT_UPDATE . " THEN '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_update_package') . "'" .
           "        WHEN log.action_id = " . FRSPackage::EVT_DELETE . " THEN '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_delete_package') . "'" .
           "        END as type" .
           "    FROM frs_log AS log" .
           "        JOIN user USING (user_id)" .
           "        JOIN frs_package ON log.item_id=frs_package.package_id" .
           "    WHERE log.group_id=" . $project->getGroupId() .
           "        AND " . logs_cond($project, $span, $who) .
           "        AND ( log.action_id=" . FRSPackage::EVT_CREATE . " OR log.action_id=" . FRSPackage::EVT_UPDATE . " OR log.action_id=" . FRSPackage::EVT_DELETE . " )" .
           " UNION" .
           "    SELECT log.log_id, log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, CONCAT(frs_package.name, '/', frs_release.name) AS title," .
           "        CASE " .
           "        WHEN log.action_id = " . FRSRelease::EVT_CREATE . " THEN '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_create_release') . "'" .
           "        WHEN log.action_id = " . FRSRelease::EVT_UPDATE . " THEN '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_update_release') . "'" .
           "        WHEN log.action_id = " . FRSRelease::EVT_DELETE . " THEN '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_delete_release') . "'" .
           "        END as type" .
           "    FROM frs_log AS log" .
           "        JOIN user using (user_id)" .
           "        JOIN frs_release ON log.item_id=frs_release.release_id " .
           "        JOIN frs_package using (package_id)" .
           "    WHERE " . logs_cond($project, $span, $who) .
           "        AND ( log.action_id=" . FRSRelease::EVT_CREATE . " OR log.action_id=" . FRSRelease::EVT_UPDATE . " OR log.action_id=" . FRSRelease::EVT_DELETE . " ) " .
           "        AND log.group_id=" . $project->getGroupId() . " " .
           " UNION" .
           "    SELECT log.log_id, log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, CONCAT(frs_package.name, '/', frs_release.name, '/', SUBSTRING_INDEX(frs_file.filename, '/', -1)) AS title," .
           "        CASE " .
           "        WHEN log.action_id = " . FRSFile::EVT_CREATE . " THEN '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_create_file') . "'" .
           "        WHEN log.action_id = " . FRSFile::EVT_UPDATE . " THEN '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_update_file') . "'" .
           "        WHEN log.action_id = " . FRSFile::EVT_DELETE . " THEN '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_delete_file') . "'" .
           "        END as type" .
           "    FROM frs_log AS log" .
           "        JOIN user using (user_id)" .
           "        JOIN frs_file ON log.item_id=frs_file.file_id" .
           "        JOIN frs_release using (release_id) " .
           "        JOIN frs_package using (package_id) " .
           "    WHERE " . logs_cond($project, $span, $who) .
           "        AND ( log.action_id=" . FRSFile::EVT_CREATE . " OR log.action_id=" . FRSFile::EVT_UPDATE . " OR log.action_id=" . FRSFile::EVT_DELETE . " )" .
           "        AND log.group_id=" . $project->getGroupId() .
           " UNION" .
           "    SELECT log.log_id, log.time AS time, 'N/A' AS user_name, 'N/A' AS realname, 'N/A' AS email, CONCAT(frs_package.name, '/', frs_release.name, '/', SUBSTRING_INDEX(frs_file.filename, '/', -1)) AS title, '" . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_restore') . "' AS type" .
           "    FROM frs_log AS log" .
           "        JOIN frs_file ON log.item_id=frs_file.file_id" .
           "        JOIN frs_release using (release_id) " .
           "        JOIN frs_package using (package_id) " .
           "    WHERE log.action_id=" . FRSFile::EVT_RESTORE .
           "        AND log.group_id=" . $project->getGroupId() .
           " UNION" .
           "    SELECT log.log_id, log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, CONCAT(frs_package.name, '/', frs_release.name, '/ ', frs_uploaded_links.link)," .
           "        CASE " .
           "        WHEN log.action_id = " . db_ei(\Tuleap\FRS\UploadedLink::EVENT_CREATE) . " THEN '" . db_es($GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_create_link')) . "'" .
           "        WHEN log.action_id = " . db_ei(\Tuleap\FRS\UploadedLink::EVENT_DELETE) . " THEN '" . db_es($GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_delete_link')) . "'" .
           "        END as type" .
           "    FROM frs_log AS log" .
           "        JOIN user USING (user_id)" .
           "        JOIN frs_uploaded_links ON log.item_id=frs_uploaded_links.id" .
           "        JOIN frs_release using (release_id) " .
           "        JOIN frs_package using (package_id) " .
           "    WHERE " . logs_cond($project, $span, $who) .
           "        AND (log.action_id=" . db_ei(\Tuleap\FRS\UploadedLink::EVENT_CREATE) . " OR log.action_id=" . db_ei(\Tuleap\FRS\UploadedLink::EVENT_DELETE) . ")" .
           "        AND log.group_id=" . $project->getGroupId() .
           " ORDER BY log_id DESC";
    return $sql;
}

function filedownload_logs_extract($project, int $span, $who)
{
    $sql = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, frs_file.filename AS title "
    . "FROM filedownload_log AS log, user, frs_file, frs_release, frs_package "
    . "WHERE " . logs_cond($project, $span, $who)
    . "AND frs_package.group_id=" . $project->getGroupId() . " "
        . "AND log.filerelease_id=frs_file.file_id "
        . "AND frs_release.release_id=frs_file.release_id "
        . "AND frs_package.package_id=frs_release.package_id "
    . "ORDER BY time DESC";

    return $sql;
}

// filedownload_logs_daily
function filedownload_logs_daily($project, int $span = 7, $who = "allusers")
{
    // check first if service is used by this project
    // if service not used return immediately
    if (! $project->usesFile()) {
        return;
    }

    $sql = filedownload_logs_extract($project, $span, $who);

    logs_display(
        $sql,
        $span,
        $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'files'),
        $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'file_download')
    );

    $sql = frs_logs_extract($project, $span, $who);
    logs_display(
        $sql,
        $span,
        $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_elements'),
        $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_actions')
    );
}

function svnaccess_logs_extract($project, int $span, $who)
{
    // Get information about the date $span days ago
    // Start at midnight $span days ago
    $time_back = localtime((time() - ($span * 86400)), 1);

    // This for debug
    // print "time_back= ". $time_back['tm_hour'].":".$time_back['tm_min'].":".$time_back['tm_sec']." on ".$time_back['tm_mday']." ".$time_back['tm_mon']." ".$time_back['tm_year']."<BR>";

    // Adjust to midnight this day
    $time_back["tm_sec"] = $time_back["tm_min"] = $time_back["tm_hour"] = 0;
    $begin_date          = mktime($time_back["tm_hour"], $time_back["tm_min"], $time_back["tm_sec"], $time_back["tm_mon"] + 1, $time_back["tm_mday"], $time_back["tm_year"] + 1900);

    $begin_day = date("Ymd", $begin_date);

    // For Debug
    // print join(" ",localtime($begin_date,0))."<BR>";
    // print "begin_day: $begin_day<BR>";

    if ($who == "allusers") {
        $cond = "";
    } else {
        $users = implode(',', $project->getMembersId());
        if ($who == "members") {
            $cond = " AND user.user_id IN ($users) ";
        } else {
            $cond = " AND user.user_id NOT IN ($users) ";
        }
    }

    // We do not show Co/up/del/add svn counters for now because
    // they are at 0 in the DB
    $sql = "SELECT group_svn_full_history.day, user.user_name, user.realname, user.email, svn_access_count, svn_browse "
    . "FROM group_svn_full_history, user "
    . "WHERE group_svn_full_history.user_id=user.user_id " . $cond
    . "AND group_svn_full_history.group_id=" . $project->getGroupId() . " "
    . "AND group_svn_full_history.day >= $begin_day "
    . "ORDER BY day DESC";

    return $sql;
}

function svnaccess_logs_daily($project, int $span = 7, $who = "allusers")
{
    $hp = Codendi_HTMLPurifier::instance();
    // check first if service is used by this project
    // if service not used return immediately
    if (! $project->usesSVN()) {
        return;
    }

    $month_name = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    $sql = svnaccess_logs_extract($project, $span, $who);

    // Executions will continue until morale improves.
    $res = db_query($sql);

    print '<P><B><U>' . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'access_for_past_x_days', [$GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'svn_access'), $hp->purify($span)]) . '</U></B></P>';

    // if there are any days, we have valid data.
    if (($nb_downloads = db_numrows($res)) >= 1) {
        print '<P><TABLE width="100%" cellpadding=2 cellspacing=0 border=0>'
        . '<TR valign="top">'
        . '<TD><B>' . $GLOBALS['Language']->getText('project_admin_utils', 'date') . '</B></TD>'
        . '<TD><B>' . $GLOBALS['Language']->getText('project_export_utils', 'user') . '</B></TD>'
        . '<TD><B>' . $GLOBALS['Language']->getText('project_export_artifact_history_export', 'email') . '</B></TD>'
        . '<TD><B>' . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'accesses') . '</B></TD>'
        . '<TD><B>' . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'browsing') . '</B></TD>'
        . '</TR>' . "\n";
        $i = 0;
        while ($row = db_fetch_array($res)) {
            $i++;
            print '<TR class="' . util_get_alt_row_color($i) . '">'
            . '<TD>' . $hp->purify(substr($row["day"], 6, 2)) . ' ' . $hp->purify($month_name[substr($row["day"], 4, 2) - 1]) . ' ' . $hp->purify(substr($row["day"], 0, 4)) . '</TD>'
             . '<TD> <a href="/users/' . $hp->purify($row["user_name"]) . '/">' . $hp->purify($row["user_name"]) . '</a> (' . $hp->purify($row["realname"]) . ')</TD>'
            . '<TD>' . $hp->purify($row["email"]) . '</TD>'
            . '<TD>' . $hp->purify($row["svn_access_count"]) . '</TD>'
            . '<TD>' . $hp->purify($row["svn_browse"]) . '</TD>'
            . '</TR>' . "\n";
        }

        print '</TABLE>';
    } else {
        echo '<P>' . $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'no_access', $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'svn_access'));
    }
}

function wiki_logs_extract($project, int $span, $who)
{
    $sql = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, log.pagename AS title"
    . " FROM wiki_log AS log, user"
    . " WHERE " . logs_cond($project, $span, $who)
    . " AND log.group_id=" . $project->getGroupId()
    . " ORDER BY time DESC";

    return $sql;
}

/**
 * Display Wiki pages access log
 */
function wiki_logs_daily($project, int $span = 7, $who = "allusers")
{
  // check first if service is used by this project
  // if service not used return immediately
    if (! $project->usesWiki()) {
        return;
    }

    $sql = wiki_logs_extract($project, $span, $who);

    logs_display(
        $sql,
        $span,
        $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'wiki_page'),
        $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'wiki_access')
    );
}

function wiki_attachments_logs_extract($project, int $span, $who)
{
    $sql = "SELECT log.time AS time, user.user_name AS user_name, user.realname AS realname, user.email AS email, wa.name AS title"
        . " FROM wiki_attachment_log AS log, user, wiki_attachment AS wa"
        . " WHERE " . logs_cond($project, $span, $who)
        . " AND log.group_id=" . $project->getGroupId()
        . " AND wa.id=log.wiki_attachment_id"
        . " ORDER BY time DESC";

    return $sql;
}

/**
 * Display Wiki Attachments access log
 */
function wiki_attachments_logs_daily($project, int $span = 7, $who = "allusers")
{
    // check first if service is used by this project
    // if service not used return immediately
    if (! $project->usesWiki()) {
        return;
    }

    $sql = wiki_attachments_logs_extract($project, $span, $who);

    logs_display(
        $sql,
        $span,
        $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'wiki_attachment_title'),
        $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'wiki_attachment_access')
    );
}

function plugins_log_extract($project, int $span, $who)
{
    $event_manager = EventManager::instance();
    $logs          = [];
    $event_manager->processEvent('logs_daily', [
        'group_id'  => $project->getGroupId(),
        'logs_cond' => logs_cond($project, $span, $who),
        'logs'      => &$logs,
        'span'      => $span,
        'who'       => $who,
    ]);
    return $logs;
}

function plugins_logs_daily($project, int $span = 7, $who = 'allusers')
{
    $logs = plugins_log_extract($project, $span, $who);
    foreach ($logs as $log) {
        logs_display($log['sql'], $span, $log['field'], $log['title']);
    }
}
