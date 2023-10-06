<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
// Copyright 1999-2000 (c) The SourceForge Crew
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

require_once __DIR__ . '/../include/pre.php';

$hp       = Codendi_HTMLPurifier::instance();
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    $vFormGrp = new Valid_UInt('form_grp');
    $vFormGrp->required();
    if ($request->valid($vFormGrp)) {
        $group_id = $request->get('form_grp');
    } else {
        exit_no_group();
    }
}

$project = ProjectManager::instance()->getProjectById((int) $group_id);
site_project_header(
    $project,
    \Tuleap\Layout\HeaderConfigurationBuilder::get($Language->getText('project_memberlist', 'proj_member_list'))
        ->inProject($project, Service::SUMMARY)
        ->build()
);

print $Language->getText('project_memberlist', 'contact_to_become_member');

// list members
// LJ email column added
$query =  "SELECT user.user_name AS user_name,user.user_id AS user_id,"
    . "user.realname AS realname, user.add_date AS add_date, "
    . "user.email AS email, "
    . "user_group.admin_flags AS admin_flags "
    . "FROM user,user_group "
    . "WHERE user.user_id=user_group.user_id AND user_group.group_id=" . db_ei($group_id) . " AND user.status IN ('A', 'R') "
    . "ORDER BY user.user_name";


$title_arr   = [];
$title_arr[] = $Language->getText('project_memberlist', 'developer');
$title_arr[] = $Language->getText('project_export_artifact_history_export', 'email');

$user_helper = new UserHelper();
$hp          = Codendi_HTMLPurifier::instance();

echo html_build_list_table_top($title_arr);

$res_memb = db_query($query);
while ($row_memb = db_fetch_array($res_memb)) {
    $display_name = $hp->purify($user_helper->getDisplayName($row_memb['user_name'], $row_memb['realname']));
    print "\t<tr>\n";
    print "\t\t";
    if ($row_memb['admin_flags'] === 'A') {
        print '<td><b><A href="/users/' . $hp->purify(urlencode($row_memb['user_name'])) . '/">' . $display_name . "</A></b></td>\n";
    } else {
        print "\t\t<td>" .  $display_name . "</td>\n";
    }

    print "\t\t<td align=\"center\"><A href=\"mailto:" . $hp->purify($row_memb['email']) . "\">" . $hp->purify($row_memb['email']) . "</A></td>\n";

    print "\t<tr>\n";
}
print "\t</table>";

site_project_footer([]);
