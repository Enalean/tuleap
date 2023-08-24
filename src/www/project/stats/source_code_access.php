<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../admin/project_admin_utils.php';
require __DIR__ . '/source_code_access_utils.php';
require __DIR__ . '/../export/access_logs_export.php';

// Only for project administrators
session_require(['group' => $group_id, 'admin_flags' => 'A']);

if (! $group_id) {
    exit_error($Language->getText('project_admin_userperms', 'invalid_g'), $Language->getText('project_admin_userperms', 'group_not_exist'));
}
$project = ProjectManager::instance()->getProject($group_id);

$who_whitelist = new Valid_WhiteList('who', ['nonmembers', 'members', 'allusers']);
$who_whitelist->required();
$who = $request->getValidated('who', $who_whitelist, 'allusers');

$unsigned_int_validator = new Valid_UInt('span');
$unsigned_int_validator->required();
$span = $request->getValidated('span', $unsigned_int_validator, 14);

$view_whitelist = new Valid_WhiteList('view', ['daily', 'weekly', 'monthly']);
$view_whitelist->required();
$view = $request->getValidated('view', $view_whitelist, 'daily');

if (isset($_REQUEST['SUBMIT'])) {
    switch ($view) {
        case "monthly":
            $period = $span * 30.5;
            break;
        case "weekly":
            $period = $span * 7;
            break;
        case 'daily':
        default:
            $period = $span;
            break;
    }

    // Send the result in CSV format
    header('Content-Type: text/csv');
    header('Content-Disposition: filename=access_logs.csv');

    if ($project->usesFile()) {
        export_file_logs($project, $period, $who);
    }
    if ($project->usesSVN()) {
        export_svn_logs($project, $period, $who);
    }
    if ($project->usesWiki()) {
        export_wiki_pg_logs($project, $period, $who, 0);
        export_wiki_att_logs($project, $period, $who);
    }
    export_all_plugins_logs($project, $period, $who);
    exit;
}

$pm = ProjectManager::instance();
project_admin_header(
    [
        'title' => $Language->getText('project_admin_index', 'p_admin', $pm->getProject($group_id)->getPublicName()),
        'group' => $group_id,
        'help' => 'project-admin.html#access-logs',
    ],
    \Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME
);

// BEGIN PAGE CONTENT CODE
echo "\n\n";

echo '<h2>' . $Language->getText('project_admin_utils', 'access_logs') . '</h2>';

print '
<FORM action="?" method="get">
<TABLE BORDER="0" WIDTH="80%">
<tr><td><b>' . $Language->getText('project_stats_source_code_access', 'access_log_from') . '</b></td><td><b>' . $Language->getText('project_stats_source_code_access', 'for_last') . '</b></td><td> </td></tr>
<tr><td>
<SELECT NAME="who">
<OPTION VALUE="nonmembers" ' . (($who == "nonmembers") ? "SELECTED" : "") . '>' . $Language->getText('project_stats_source_code_access', 'non_proj_members') . '</OPTION>
<OPTION VALUE="members" ' . (($who == "members") ? "SELECTED" : "") . '>' . $Language->getText('project_admin_editugroup', 'proj_members') . '</OPTION>
<OPTION VALUE="allusers" ' . (($who == "allusers") ? "SELECTED" : "") . '>' . $Language->getText('project_stats_source_code_access', 'all_users') . '</OPTION>
</SELECT></td>
<td> 
<SELECT NAME="span">
<OPTION VALUE="4" ' . (($span == 4) ? "SELECTED" : "") . '>4</OPTION>
<OPTION VALUE="7" ' . (($span == 7 || ! isset($span) ) ? "SELECTED" : "") . '>7</OPTION>
<OPTION VALUE="12" ' . (($span == 12) ? "SELECTED" : "") . '>12</OPTION>
<OPTION VALUE="14" ' . (($span == 14) ? "SELECTED" : "") . '>14</OPTION>
<OPTION VALUE="30" ' . (($span == 30) ? "SELECTED" : "") . '>30</OPTION>
<OPTION VALUE="52" ' . (($span == 52) ? "SELECTED" : "") . '>52</OPTION>
</SELECT>

<SELECT NAME="view">
<OPTION VALUE="monthly" ' . (($view == "monthly") ? "SELECTED" : "") . '>' . $Language->getText('project_stats_index', 'months') . '</OPTION>
<OPTION VALUE="weekly" ' . (($view == "weekly") ? "SELECTED" : "") . '>' . $Language->getText('project_stats_index', 'weeks') . '</OPTION>
<OPTION VALUE="daily" ' . (($view == "daily" || ! isset($view)) ? "SELECTED" : "") . '>' . $Language->getText('project_stats_index', 'days') . '</OPTION>
</SELECT>
</td>
<td>
 
<INPUT type="submit" value="' . $Language->getText('global', 'btn_browse') . '">
<INPUT type="hidden" name="group_id" value="' . $group_id . '">
</td></tr></table></FORM>';

switch ($view) {
    case "monthly":
        print '<P>';
        $span_days = (int) ($span * 30.5);
        filedownload_logs_daily($project, $span_days, $who);
        svnaccess_logs_daily($project, $span_days, $who);
        wiki_logs_daily($project, $span_days, $who);
        wiki_attachments_logs_daily($project, $span_days, $who);
        plugins_logs_daily($project, $span_days, $who);
        break;

    case "weekly":
        print '<P>';
        filedownload_logs_daily($project, $span * 7, $who);
        svnaccess_logs_daily($project, $span * 7, $who);
        wiki_logs_daily($project, $span * 7, $who);
        wiki_attachments_logs_daily($project, $span * 7, $who);
        plugins_logs_daily($project, $span * 7, $who);
        break;

    case 'daily':
    default:
        filedownload_logs_daily($project, $span, $who);
        svnaccess_logs_daily($project, $span, $who);
        wiki_logs_daily($project, $span, $who);
        wiki_attachments_logs_daily($project, $span, $who);
        plugins_logs_daily($project, $span, $who);
}

$purifier = Codendi_HTMLPurifier::instance();
echo '<BR><FORM METHOD="POST" NAME="access_logs_export_form">
	<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $purifier->purify($group_id) . '">
	<INPUT TYPE="HIDDEN" NAME="who" VALUE="' . $purifier->purify($who) . '">
	<INPUT TYPE="HIDDEN" NAME="span" VALUE="' . $purifier->purify($span) . '">
	<INPUT TYPE="HIDDEN" NAME="view" VALUE="' . $purifier->purify($view) . '">
	<TABLE align="left"><TR><TD>
	<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="' . $GLOBALS['Language']->getText('project_stats_source_code_access', 'logs_export') . '">
	</TD></TR></TABLE></FORM>';

print '<BR><P>';

// END PAGE CONTENT CODE
site_project_footer([]);
