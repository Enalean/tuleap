<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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

require_once __DIR__ . '/project_export_utils.php';

//    get the Group object
$pm = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (!$group || !is_object($group) || $group->isError()) {
    exit_no_group();
}

assert(isset($atid));
if ($atid) {
    //    Create the ArtifactType object
    $at = new ArtifactType($group, $atid);
    if (!$at || !is_object($at)) {
        exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('project_export_artifact_deps_export', 'at_not_created'));
    }
    if ($at->isError()) {
        exit_error($GLOBALS['Language']->getText('global', 'error'), $at->getErrorMessage());
    }

    // Create field factory
    $art_field_fact = new ArtifactFieldFactory($at);
    if ($art_field_fact->isError()) {
        exit_error($GLOBALS['Language']->getText('global', 'error'), $art_field_fact->getErrorMessage());
    }
}

function extract_history($atid)
{
    // This is the SQL query to retrieve all the artifact history for this group
    // Note: the text value of the comment_type_id is not fetched by the
    // SQL statement because this field can be NULL and the NULL value is
    // not a valid comment type in the bug_field_value table. So the join
    // doesn't work.
    $sql = sprintf(
        '(SELECT ah.artifact_id, ah.field_name, ah.old_value, ah.new_value, user.user_name AS mod_by, user.email, ah.date, ah.type, af.label' .
                    ' FROM artifact_history ah, user, artifact a, artifact_field af' .
                    ' WHERE ah.artifact_id = a.artifact_id' .
                    ' AND a.group_artifact_id = %d' .
                    ' AND af.group_artifact_id = a.group_artifact_id' .
                    ' AND user.user_id = ah.mod_by' .
                    ' AND ah.field_name = af.field_name)' .
                    ' UNION' .
                    ' (SELECT ah.artifact_id, ah.field_name, ah.old_value, ah.new_value, user.user_name AS mod_by, user.email, ah.date, ah.type, ah.field_name' .
                    ' FROM artifact_history ah, user, artifact a' .
                    ' WHERE ah.artifact_id = a.artifact_id' .
                    ' AND a.group_artifact_id = %d' .
                    ' AND user.user_id = ah.mod_by' .
                    ' AND (ah.field_name = "%s" OR ah.field_name = "%s" OR ah.field_name LIKE "%s"))' .
                    ' ORDER BY artifact_id, date DESC',
        $atid,
        $atid,
        "cc",
        "attachment",
        "lbl_%_comment"
    );
    return db_query($sql);
}

$col_list = array('artifact_id','field_name','old_value','new_value','mod_by','email','date','type','label');
$lbl_list = array('artifact_id' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'art_id'),
          'field_name' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'field_name'),
          'old_value' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'old_val'),
          'new_value' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'new_val'),
          'mod_by' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'mod_by'),
          'email' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'email'),
          'date' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'mod_on'),
          'type' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'comment_type'),
          'label' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'label')
                  );

$dsc_list = array('artifact_id' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'art_id'),
          'field_name' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'field_name_desc'),
          'old_value' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'old_val_desc'),
          'new_value' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'new_val_desc'),
          'mod_by' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'mod_by_desc'),
          'date' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'mod_on_desc'),
          'type' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'comment_type_desc'),
          'label' => $GLOBALS['Language']->getText('project_export_artifact_history_export', 'label_desc'));

$eol = "\n";

$result = extract_history($atid);
$rows = db_numrows($result);

$export = isset($export) ? (string) $export : '';
if ($export == 'artifact_history') {
    // Send the result in CSV format
    if ($result && $rows > 0) {
        assert(isset($at, $art_field_fact));
        $tbl_name = str_replace(' ', '_', 'artifact_history_' . $at->getItemName());
        header('Content-Type: text/csv');
        header('Content-Disposition: filename=' . $tbl_name . '.csv');
        echo build_csv_header($col_list, $lbl_list) . $eol;

        while ($arr = db_fetch_array($result)) {
            prepare_artifact_history_record($at, $art_field_fact, $arr);
            echo build_csv_record($col_list, $arr) . $eol;
        }
    } else {
        assert(isset($pg_title));
        project_admin_header(array('title' => $pg_title), 'data');

        echo '<h3>' . $GLOBALS['Language']->getText('project_export_artifact_history_export', 'art_hist_export') . '</h3>';
        if ($result) {
            echo '<P>' . $GLOBALS['Language']->getText('project_export_artifact_history_export', 'no_hist_found');
        } else {
            echo '<P>' . $GLOBALS['Language']->getText('project_export_artifact_history_export', 'db_access_err', $GLOBALS['sys_name']);
            echo '<br>' . db_error();
        }
        site_project_footer(array());
    }
} elseif ($export == "artifact_history_format") {
    assert(isset($at, $art_field_fact));
    echo '<h3>' . $GLOBALS['Language']->getText('project_export_artifact_history_export', 'hist_export_format') . '</h3>';
    echo '<p>' . $GLOBALS['Language']->getText('project_export_artifact_history_export', 'hist_export_format_msg') . '</p>';

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_artifact_history_record($at, $art_field_fact, $record);
    display_exported_fields($col_list, $lbl_list, $dsc_list, $record);
}
