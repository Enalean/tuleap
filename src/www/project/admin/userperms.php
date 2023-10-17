<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/project_admin_utils.php';
require_once __DIR__ . '/ugroup_utils.php';

use Tuleap\FRS\FRSPermissionManager;

//  get the Group object
$pm = ProjectManager::instance();
if (! isset($group_id)) {
    $group_id = 0;
}
$group = $pm->getProject($group_id);
if (! $group || ! is_object($group) || $group->isError()) {
    exit_no_group();
}
$atf = new ArtifactTypeFactory($group);
if (! $group || ! is_object($group) || $group->isError()) {
    exit_error($Language->getText('global', 'error'), $Language->getText('project_admin_index', 'not_get_atf'));
}
// Get the artfact type list
$at_arr = $atf->getArtifactTypes();

session_require(['group' => $group_id, 'admin_flags' => 'A']);

$project = $pm->getProject($group_id);
if ($project->isError()) {
        //wasn't found or some other problem
        echo $Language->getText('project_admin_userperms', 'unable_load_p') . "<br>";
        return;
}

// ########################### form submission, make updates
if ($request->exist('submit')) {
    (new ProjectHistoryDao())->groupAddHistory('changed_member_perm', '', $group_id);
    $nb_errors = 0;

    $res_dev = db_query("SELECT * FROM user_group WHERE group_id=" . db_ei($group_id));
    while ($row_dev = db_fetch_array($res_dev)) {
        if ($request->exist("update_user_$row_dev[user_id]")) {
            $svn_flags = "svn_user_$row_dev[user_id]";
            $res       = true;
            if ($request->exist($svn_flags)) {
                $sql  = "UPDATE user_group SET svn_flags = '" . db_es($request->get($svn_flags)) . "'";
                $sql .= " WHERE user_id=" . db_ei($row_dev['user_id']) . " AND group_id=" . db_ei($group_id);

                $res = db_query($sql);
            }

            $tracker_error = false;
            if ($project->usesTracker() && $at_arr) {
                for ($j = 0; $j < count($at_arr); $j++) {
                    $atid       = $at_arr[$j]->getID();
                    $perm_level = "tracker_user_$row_dev[user_id]_$atid";
                    if ($at_arr[$j]->existUser($row_dev['user_id'])) {
                        if (! $at_arr[$j]->updateUser($row_dev['user_id'], $request->get($perm_level))) {
                            echo $at_arr[$j]->getErrorMessage();
                            $tracker_error = true;
                        }
                    } else {
                        if (! $at_arr[$j]->addUser($row_dev['user_id'], $request->get($perm_level))) {
                            $tracker_error = true;
                        }
                    }
                }
            }

            if (! $res || $tracker_error) {
                $nb_errors++;
                $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_userperms', 'perm_fail_for', $row_dev['user_id']) . ' ' . db_error());
            }

            // Raise an event
            $em = EventManager::instance();
            $em->processEvent('project_admin_change_user_permissions', [
                'group_id' => $group_id,
                'user_id' => $row_dev['user_id'],
            ]);
        }
    }

    if (count($row_dev) > $nb_errors) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('project_admin_userperms', 'perm_upd'));
    }
}

$vPattern = new Valid_String('search');
$vPattern->required();
if ($request->valid($vPattern)) {
    $pattern = $request->get('search');
} else {
    $pattern = '';
}

$offset = $request->getValidated('offset', 'uint', 0);
if (! $offset) {
    $offset = 0;
}
$number_per_page = 25;

$sql           = [];
$sql['select'] = "SELECT SQL_CALC_FOUND_ROWS user.user_name AS user_name,
                  user.realname AS realname,
                  user.user_id AS user_id,
                  user_group.bug_flags,
                  user_group.project_flags,
                  user_group.patch_flags,
                  user_group.file_flags,
                  user_group.support_flags,
                  user_group.svn_flags";

$sql['from']  = " FROM user,user_group ";
$sql['where'] = " WHERE user.user_id = user_group.user_id
                    AND user_group.group_id = " . db_ei($group_id);

if ($pattern) {
    $uh            = UserHelper::instance();
    $sql['filter'] = $uh->getUserFilter($pattern);
} else {
    $sql['filter'] = '';
}

$sql['order'] = " ORDER BY user.user_name ";
$sql['limit'] = " LIMIT " . db_ei($offset) . ", " . db_ei($number_per_page);

if ($project->usesTracker() && $at_arr) {
    for ($j = 0; $j < count($at_arr); $j++) {
        $atid           = db_ei($at_arr[$j]->getID());
        $sql['select'] .= ", IFNULL(artifact_perm_" . $atid . ".perm_level, 0) AS perm_level_" . $atid . " ";
        $sql['from']   .= " LEFT JOIN artifact_perm AS artifact_perm_" . $atid . "
                                 ON(artifact_perm_" . $atid . ".user_id = user_group.user_id
                                    AND artifact_perm_" . $atid . ".group_artifact_id = " . $atid . ") ";
    }
}
$res_dev = db_query($sql['select'] . $sql['from'] . $sql['where'] . $sql['filter'] . $sql['order'] . $sql['limit']);

if (! $res_dev || db_numrows($res_dev) == 0 || $number_per_page < 1) {
    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_userperms', 'no_users_found'));
}
$sql            = 'SELECT FOUND_ROWS() AS nb';
$res            = db_query($sql);
$row            = db_fetch_array($res);
$num_total_rows = $row['nb'];

project_admin_header(
    $Language->getText('project_admin_utils', 'user_perms'),
    \Tuleap\Project\Admin\Navigation\NavigationPermissionsDropdownPresenterBuilder::PERMISSIONS_ENTRY_SHORTNAME
);

$purifier = Codendi_HTMLPurifier::instance();

echo '
<h2>' . $Language->getText('project_admin_utils', 'user_perms') . '</h2>';
echo '<FORM action="userperms.php" name = "form_search" method="post" class="form-inline">';

echo $Language->getText('project_admin_utils', 'search_user');
echo '&nbsp;';
echo '<div class="input-append">';
echo '<INPUT type="text" name="search" value="' . $purifier->purify($pattern) . '" id="search_user">
<INPUT type="hidden" name="group_id" value="' . $purifier->purify($group_id) . '">';
$js = "new UserAutoCompleter('search_user',
                          '" . util_get_dir_image_theme() . "',
                          true);";
$GLOBALS['Response']->includeFooterJavascriptSnippet($js);

echo '<INPUT class="btn" type="submit" name ="searchUser" value="' . _('Search') . '"></div>';
echo '</FORM>';

$frs_permission_manager = FRSPermissionManager::build();

if ($res_dev && db_numrows($res_dev) > 0 && $number_per_page > 0) {
    echo '<FORM action="userperms.php" name= "form_update" method="post">
<INPUT type="hidden" name="group_id" value="' . $purifier->purify($group_id) . '">
<INPUT type="hidden" name="offset" value="' . $purifier->purify($offset) . '">';

    echo '<TABLE class="table">';

    $head = '<thead><tr>';
    $i    = 0;

    $should_display_submit_button = false;

    $head .= '<th>' . $Language->getText('project_admin_userperms', 'user_name') . '</th>';

    if ($project->usesSVN()) {
        $should_display_submit_button = true;
        $head                        .= '<th>' . $Language->getText('project_admin_userperms', 'svn') . '</th>';
    }

    if ($project->usesTracker() && $at_arr) {
        $should_display_submit_button = true;
        for ($j = 0; $j < count($at_arr); $j++) {
              $head .= '<th>' . $purifier->purify($at_arr[$j]->getName()) . '</th>';
        }
    }

    $head .= '</tr></thead><tbody>';

    echo $head;

    $i = 0;

    $uh = new UserHelper();

    while ($row_dev = db_fetch_array($res_dev)) {
        $i++;
        print '<TR class="' . util_get_alt_row_color($i) . '">';
        $user_name = $purifier->purify($uh->getDisplayName($row_dev['user_name'], $row_dev['realname']), CODENDI_PURIFIER_CONVERT_HTML);
        echo '<td><input type="hidden" name="' . $purifier->purify('update_user_' . $row_dev['user_id']) . '">' . $user_name . '</td>';
     // svn
        if ($project->usesSVN()) {
            $cell  = '';
            $cell .= '<TD><SELECT name="' . $purifier->purify('svn_user_' . $row_dev['user_id']) . '">';
            $cell .= '<OPTION value="0"' . (($row_dev['svn_flags'] == 0) ? " selected" : "") . '>' . $Language->getText('global', 'none');
            $cell .= '<OPTION value="2"' . (($row_dev['svn_flags'] == 2) ? " selected" : "") . '>' . $Language->getText('project_admin_index', 'admin');
            $cell .= '</SELECT></TD>';
            echo $cell;
        }

        $k = 0;
        if ($project->usesTracker() && $at_arr) {
            // Loop on tracker
            for ($j = 0; $j < count($at_arr); $j++) {
                $atid  = $at_arr[$j]->getID();
                $perm  = $row_dev['perm_level_' . $atid];
                $cell  = '';
                $cell .= '<TD><SELECT name="' . $purifier->purify('tracker_user_' . $row_dev['user_id']) . '_' . $purifier->purify($atid) . '">';
                $cell .= '<OPTION value="0"' . (($perm == 0) ? " selected" : "") . '>' . $Language->getText('global', 'none');
                $cell .= '<OPTION value="3"' . (($perm == 3 || $perm == 2) ? " selected" : "") . '>' . $Language->getText('project_admin_userperms', 'admin');
                $cell .= '</SELECT></TD>';
                echo $cell;
            }
        }

        print '</TR>';
        if ($i % 10 == 0) {
            echo $head;
        }
    } // while



    echo '</tbody>
    </table>';
    if ($num_total_rows && $number_per_page < $num_total_rows) {
        //Jump to page
        $nb_of_pages  = ceil($num_total_rows / $number_per_page);
        $current_page = round($offset / $number_per_page);

        echo '<div style="font-family:Verdana">Page: ';
        $width = 10;
        for ($i = 0; $i < $nb_of_pages; ++$i) {
            if ($i == 0 || $i == $nb_of_pages - 1 || ($current_page - $width / 2 <= $i && $i <= $width / 2 + $current_page)) {
                $link_parameters = ['group_id' => $group_id, 'offset' => $i * $number_per_page];
                if (isset($pattern) && $pattern !== '') {
                    $link_parameters['search'] = $pattern;
                }
                echo '<a href="?' . $purifier->purify(http_build_query($link_parameters)) . '">';
                if ($i == $current_page) {
                    echo '<b>' . $purifier->purify($i + 1) . '</b>';
                } else {
                    echo $purifier->purify($i + 1);
                }
                echo '</a>&nbsp;';
            } elseif ($current_page - $width / 2 - 1 == $i || $current_page + $width / 2 + 1 == $i) {
                echo '...&nbsp;';
            }
        }
        echo '</div>';
    }

    if ($should_display_submit_button) {
        echo '<P align="center"><INPUT type="submit" name="submit" value="' . $Language->getText('project_admin_userperms', 'upd_user_perm') . '">';
    }
    echo '</FORM>';
}

project_admin_footer([]);
