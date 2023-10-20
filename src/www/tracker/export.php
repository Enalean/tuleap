<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

require_once __DIR__ . '/../project/export/project_export_utils.php';
//  make sure this person has permission to view artifacts
if (! $ath->userCanView()) {
    exit_permission_denied();
}

// Check if this tracker is valid (not deleted)
if (! $ath->isValid()) {
    exit_error($Language->getText('global', 'error'), $GLOBALS['Language']->getText('tracker_add', 'invalid'));
}

$request     = HTTPRequest::instance();
$export_aids = $request->get('export_aids');
$constraint  = 'AND a.artifact_id IN (' . db_es($export_aids) . ')';

$export_select    = $export_from = $export_where = '';
$multiple_queries = false;
$fields           = $col_list = $lbl_list = $dsc_list = $all_queries      = [];

$sql = $ath->buildExportQuery($fields, $col_list, $lbl_list, $dsc_list, $export_select, $export_from, $export_where, $multiple_queries, $all_queries, $constraint);
assert(is_bool($multiple_queries));

// Normally these two fields should be part of the artifact_fields.
// For now big hack:
// As we don't know the projects language, we export it according to the user language preferences

$lbl_list['follow_ups']      = $GLOBALS['Language']->getText('project_export_artifact_export', 'follow_up_comments');
$lbl_list['is_dependent_on'] = $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on');
$lbl_list['cc']              = $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_lbl');

$dsc_list['follow_ups']      = $GLOBALS['Language']->getText('project_export_artifact_export', 'all_followup_comments');
$dsc_list['is_dependent_on'] = $GLOBALS['Language']->getText('project_export_artifact_export', 'depend_on_list');
$dsc_list['cc']              = $GLOBALS['Language']->getText('project_export_artifact_export', 'add_cc_dsc');

// Add the 2 fields that we build ourselves for user convenience
// - All follow-up comments
// - Dependencies
// - CC List

$col_list[] = 'follow_ups';
$col_list[] = 'is_dependent_on';
$col_list[] = 'cc';

$eol = "\n";

// If user asked to export only displayed fields (fields displayed in the current report)
// The export is based on the arrays col_list and lbl_list, that contain the fields to export.
// Basically, these arrays contain all the fields of the tracker,
// so we simply remove the non-displayed fields from these arrays.
if ($request->get('only_displayed_fields') == 'on') {
    assert(isset($atid));
    $artifact_report  = new ArtifactReport($request->get('report_id'), $atid);
    $displayed_fields = $artifact_report->getResultFields();
    // array_intersect_key is a PHP 5 function (implemented here in src/www/include/utils.php)
    $col_list = array_intersect_key($col_list, $displayed_fields);
    $lbl_list = array_intersect_key($lbl_list, $displayed_fields);
}

//$sql = $export_select." ".$export_from." ".$export_where." AND a.artifact_id IN ($export_aids) group by a.artifact_id";

if ($multiple_queries) {
    $all_results = [];
    foreach ($all_queries as $q) {
        $result        = db_query($q);
        $all_results[] = $result;
        $rows          = db_numrows($result);
    }
} else {
    $result = db_query($sql);
    $rows   = db_numrows($result);
}

// Send the result in CSV format
if ($result && $rows > 0) {
    $http      = Codendi_HTTPPurifier::instance();
    $file_name = str_replace(' ', '_', 'artifact_' . $ath->getItemName());
    header('Content-Type: text/csv');
    header('Content-Disposition: filename=' . $http->purify($file_name) . '_' . $ath->Group->getUnixName() . '.csv');

    foreach ($lbl_list as $k => $v) {
        $lbl_list[$k] = SimpleSanitizer::unsanitize($v);
    }
    echo build_csv_header($col_list, $lbl_list) . $eol;

    if ($multiple_queries) {
        $multiarr = [];
        for ($i = 0; $i < $rows; $i++) {
            foreach ($all_results as $result) {
                $multiarr = array_merge($multiarr, db_fetch_array($result));
            }

            prepare_artifact_record($ath, $fields, $atid, $multiarr, 'csv');
            $curArtifact = new Artifact($ath, $multiarr['artifact_id']);
            if ($curArtifact->userCanView()) {
                echo build_csv_record($col_list, $multiarr) . $eol;
            }
        }
    } else {
        while ($arr = db_fetch_array($result)) {
            prepare_artifact_record($ath, $fields, $atid, $arr, 'csv');
            $curArtifact = new Artifact($ath, $arr['artifact_id']);
            if ($curArtifact->userCanView()) {
                echo build_csv_record($col_list, $arr) . $eol;
            }
        }
    }
} else {
    $params['toptab']   = 'tracker';
    $params['pagename'] = 'trackers';
    $params['title']    = $Language->getText('tracker_index', 'trackers_for');
    $params['pv']       = $request->exist('pv') ? $request->get('pv') : '';
    site_project_header(ProjectManager::instance()->getProjectById((int) $group_id), $params);

    echo '<h3>' . $Language->getText('project_export_artifact_export', 'art_export') . '</h3>';
    if ($result) {
        echo '<P>' . $Language->getText('project_export_artifact_export', 'no_art_found');
    } else {
        echo '<P>' . $Language->getText('project_export_artifact_export', 'db_access_err', ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
        echo '<br>' . db_error();
    }
    site_project_footer([]);
}
