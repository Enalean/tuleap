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

//    get the Group object
$pm = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (!$group || !is_object($group) || $group->isError()) {
    exit_no_group();
}

if ($atid) {
    //    Create the ArtifactType object
    $at = new ArtifactType($group, $atid);
    if (!$at || !is_object($at)) {
        exit_error($Language->getText('global', 'error'), $Language->getText('project_export_artifact_deps_export', 'at_not_created'));
    }
    if ($at->isError()) {
        exit_error($Language->getText('global', 'error'), $at->getErrorMessage());
    }

        //      Create the ArtifactTypeHtml object - needed in ArtifactField.getFieldPredefinedValues()
        $ath = new ArtifactTypeHtml($group, $atid);
    if (!$ath || !is_object($ath)) {
        exit_error($Language->getText('global', 'error'), $Language->getText('project_export_artifact_export', 'ath_not_created'));
    }
    if ($ath->isError()) {
        exit_error($Language->getText('global', 'error'), $ath->getErrorMessage());
    }

    // Create field factory
    $art_field_fact = new ArtifactFieldFactory($at);
    if ($art_field_fact->isError()) {
        exit_error($Language->getText('global', 'error'), $art_field_fact->getErrorMessage());
    }
    $art_fieldset_fact = new ArtifactFieldSetFactory($at);
    if ($art_fieldset_fact->isError()) {
        exit_error($Language->getText('global', 'error'), $art_fieldset_fact->getErrorMessage());
    }

    $sql = $at->buildExportQuery($fields, $col_list, $lbl_list, $dsc_list, $select, $from, $where, $multiple_queries, $all_queries);

    // Normally these two fields should be part of the artifact_fields.
    // For now big hack:
    // As we don't know the projects language, we export it according to user language preferences
    $lbl_list['follow_ups']      = $Language->getText('project_export_artifact_export', 'follow_up_comments');
    $lbl_list['is_dependent_on'] = $Language->getText('project_export_artifact_export', 'depend_on');

    $dsc_list['follow_ups'] = $Language->getText('project_export_artifact_export', 'all_followup_comments');
    $dsc_list['is_dependent_on'] = $Language->getText('project_export_artifact_export', 'depend_on_list');
}


// Add the 2 fields that we build ourselves for user convenience
// - All follow-up comments
// - Dependencies

$col_list[] = 'follow_ups';
$col_list[] = 'is_dependent_on';


$eol = "\n";

//echo "DBG -- $sql<br>";

if (isset($multiple_queries) && $multiple_queries) {
    $all_results = array();
    foreach ($all_queries as $q) {
        $result = db_query($q);
        $all_results[] = $result;
        $rows = db_numrows($result);
    }
} else {
    $result=db_query($sql);
    $rows = db_numrows($result);
}

if ($export == 'artifact') {
    // Send the result in CSV format
    if ($result && $rows > 0) {
            $tbl_name = str_replace(' ', '_', 'artifact_'.$at->getItemName());
        header('Content-Type: text/csv');
        header('Content-Disposition: filename='.$tbl_name.'_'.$dbname.'.csv');

        foreach ($lbl_list as $k => $v) {
            $lbl_list[$k] = SimpleSanitizer::unsanitize($v);
        }
        echo build_csv_header($col_list, $lbl_list).$eol;

        if ($multiple_queries) {
            $multiarr = array();
            for ($i = 0; $i < $rows; $i++) {
                foreach ($all_results as $result) {
                      $multiarr = array_merge($multiarr, db_fetch_array($result));
                }

                prepare_artifact_record($ath, $fields, $atid, $multiarr, 'csv');
                echo build_csv_record($col_list, $multiarr).$eol;
            }
        } else {
            while ($arr = db_fetch_array($result)) {
                prepare_artifact_record($at, $fields, $atid, $arr, 'csv');
                echo build_csv_record($col_list, $arr).$eol;
            }
        }
    } else {
        project_admin_header(array('title'=>$pg_title), 'data');

        echo '<h3>'.$Language->getText('project_export_artifact_export', 'art_export').'</h3>';
        if ($result) {
            echo '<P>'.$Language->getText('project_export_artifact_export', 'no_art_found');
        } else {
            echo '<P>'.$Language->getText('project_export_artifact_export', 'db_access_err', $GLOBALS['sys_name']);
            echo '<br>'.db_error();
        }
        site_project_footer(array());
    }
} elseif ($export == "artifact_format") {
    echo '<h3>'.$Language->getText('project_export_artifact_export', 'art_exp_format').'</h3>';

    echo '<p>'.$Language->getText('project_export_artifact_export', 'art_exp_format_msg').'</p>';

    $record = pick_a_record_at_random($result, $rows, $col_list);
    prepare_artifact_record($at, $fields, $atid, $record, 'csv');
    display_exported_fields($col_list, $lbl_list, $dsc_list, $record);
}
