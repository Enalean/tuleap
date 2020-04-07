<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../admin/project_admin_utils.php';
require_once __DIR__ . '/project_export_utils.php';

// Inherited from old .htaccess
ini_set('max_execution_time', 3600);
ini_set('memory_limit', '256M');

$em       = EventManager::instance();
$request  = HTTPRequest::instance();
$export   = $request->get('export');
$group_id = $request->get('group_id');
if (!isset($export)) {
    $export = "";
}

// Group ID must be defined and must be a project admin
if (!$group_id) {
    exit_error($Language->getText('project_admin_userperms', 'invalid_g'), $Language->getText('project_admin_userperms', 'group_not_exist'));
}

session_require(array('group' => $group_id,'admin_flags' => 'A'));

//  get the Group object
$pm = ProjectManager::instance();
$group = $pm->getProject($group_id);
if (!$group || !is_object($group) || $group->isError()) {
    exit_no_group();
}
$atf = new ArtifactTypeFactory($group);
if (!$group || !is_object($group) || $group->isError()) {
    exit_error($Language->getText('global', 'error'), $Language->getText('project_admin_index', 'not_get_atf'));
}

$project = $pm->getProject($group_id);
$groupname = $project->getUnixName();
$pg_title = $Language->getText('project_admin_utils', 'project_data_export') . ' ' . $groupname;

$em->processEvent('project_export', array('export' => $export, 'project' => $project));

switch ($export) {
    case 'artifact':
        require('./artifact_export.php');
        break;

    case 'artifact_format':
        project_admin_header(array('title' => $pg_title), NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./artifact_export.php');
        site_project_footer(array());
        break;

    case 'artifact_history':
        require('./artifact_history_export.php');
        break;

    case 'artifact_history_format':
        project_admin_header(array('title' => $pg_title), NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./artifact_history_export.php');
        site_project_footer(array());
        break;

    case 'artifact_deps':
        require('./artifact_deps_export.php');
        break;

    case 'artifact_deps_format':
        project_admin_header(array('title' => $pg_title), NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./artifact_deps_export.php');
        site_project_footer(array());
        break;

    case 'access_logs':
        require('./access_logs_export.php');
        break;

    case 'access_logs_format':
        project_admin_header(array('title' => $pg_title), NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./access_logs_export.php');
        site_project_footer(array());
        break;

    case 'user_groups':
        require('./user_groups_export.php');
        break;

    case 'user_groups_format':
        project_admin_header(array('title' => $pg_title), NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
        require('./user_groups_export.php');
        site_project_footer(array());
        break;

    default:
        project_admin_header(array('title' => $pg_title, 'help' => 'project-admin.html#project-data-export'), NavigationPresenterBuilder::DATA_ENTRY_SHORTNAME);
     // Display the welcome screen
        echo '
<h3> ' . $Language->getText('project_export_index', 'export_to_csv_hdr', array(help_button('project-admin.html#text-file-export'))) . '</h3>';

        echo '
<p> ' . $Language->getText('project_export_index', 'export_to_csv_msg') . '</p>';

     // Show all the fields currently available in the system
        $entry_label                           = array();
        $entry_data_export_links               = array();
        $entry_data_export_format_links        = array();
        $history_data_export_links             = array();
        $history_data_export_format_links      = array();
        $dependencies_data_export_links        = array();
        $dependencies_data_export_format_links = array();
        $iu = 0;

        $titles = array('',
                     $Language->getText('project_export_index', 'art_data'),
                     $Language->getText('project_export_index', 'history'),
                     $Language->getText('project_export_index', 'dependencies'));
        echo html_build_list_table_top($titles);

        if ($project->usesTracker()) {
            // Get the artfact type list
            $at_arr = $atf->getArtifactTypes();
            if ($at_arr && count($at_arr) >= 1) {
                foreach ($at_arr as $at) {
                    $idx = 'tracker_' . $at->getID();
                    $entry_label[$idx]                           = $Language->getText('project_export_index', 'tracker') . ': ' . $at->getName();
                    $entry_data_export_links[$idx]               = '?group_id=' . $group_id . '&atid=' . $at->getID() . '&export=artifact';
                    $entry_data_export_format_links[$idx]        = '?group_id=' . $group_id . '&atid=' . $at->getID() . '&export=artifact_format';
                    $history_data_export_links[$idx]             = '?group_id=' . $group_id . '&atid=' . $at->getID() . '&export=artifact_history';
                    $history_data_export_format_links[$idx]      = '?group_id=' . $group_id . '&atid=' . $at->getID() . '&export=artifact_history_format';
                    $dependencies_data_export_links[$idx]        = '?group_id=' . $group_id . '&atid=' . $at->getID() . '&export=artifact_deps';
                    $dependencies_data_export_format_links[$idx] = '?group_id=' . $group_id . '&atid=' . $at->getID() . '&export=artifact_deps_format';
                }
            }
        }

    // Access log
        $entry_label['access_log']                           = $Language->getText('project_export_index', 'access_logs');
        $entry_data_export_links['access_log']               = '?group_id=' . $group_id . '&export=access_logs';
        $entry_data_export_format_links['access_log']        = null;
        $history_data_export_links['access_log']             = null;
        $history_data_export_format_links['access_log']      = null;
        $dependencies_data_export_links['access_log']        = null;
        $dependencies_data_export_format_links['access_log'] = null;

    // User groups definitions
        $entry_label['user_groups']                           = $Language->getText('project_export_index', 'user_groups');
        $entry_data_export_links['user_groups']               = '?group_id=' . $group_id . '&export=user_groups';
        $entry_data_export_format_links['user_groups']        = '?group_id=' . $group_id . '&export=user_groups_format';
        $history_data_export_links['user_groups']             = null;
        $history_data_export_format_links['user_groups']      = null;
        $dependencies_data_export_links['user_groups']        = null;
        $dependencies_data_export_format_links['user_groups'] = null;

    // Plugins entries
        $exportable_items = array(
                        'group_id' => $group_id,
                        'labels' => &$entry_label,
                        'data_export_links' => &$entry_data_export_links,
                        'data_export_format_links' => &$entry_data_export_format_links,
                        'history_export_links' => &$history_data_export_links,
                        'history_export_format_links' => &$history_data_export_format_links,
                        'dependencies_export_links' => &$dependencies_data_export_links,
                        'dependencies_export_format_links' => &$dependencies_data_export_format_links);
        $em->processEvent('project_export_entry', $exportable_items);

        function key_exists_and_value_not_null($key, array $array)
        {
            return (isset($array[$key]) && $array[$key] != null);
        }

        foreach ($exportable_items['labels'] as $key => $label) {
            echo '<tr class="' . util_get_alt_row_color($iu) . '">';
            echo ' <td><b>' . $label . '</b></td>';
            echo ' <td align="center">';
            if (key_exists_and_value_not_null($key, $entry_data_export_links)) {
                echo '  <a href="' . $entry_data_export_links[$key] . '">' . $Language->getText('project_export_index', 'export') . '</a>';
            } else {
                echo '-';
            }
            echo '  <br>';
            if (key_exists_and_value_not_null($key, $entry_data_export_format_links)) {
                echo '  <a href="' . $entry_data_export_format_links[$key] . '">' . $Language->getText('project_export_index', 'show_format') . '</a>';
            } else {
                echo '-';
            }
            echo ' </td>';
            echo ' <td align="center">';
            if (key_exists_and_value_not_null($key, $history_data_export_links)) {
                echo '  <a href="' . $history_data_export_links[$key] . '">' . $Language->getText('project_export_index', 'export') . '</a>';
            } else {
                echo '-';
            }
            echo '  <br>';
            if (key_exists_and_value_not_null($key, $history_data_export_format_links)) {
                echo '  <a href="' . $history_data_export_format_links[$key] . '">' . $Language->getText('project_export_index', 'show_format') . '</a>';
            } else {
                echo '-';
            }
            echo ' </td>';
            echo ' <td align="center">';
            if (key_exists_and_value_not_null($key, $dependencies_data_export_links)) {
                echo '  <a href="' . $dependencies_data_export_links[$key] . '">' . $Language->getText('project_export_index', 'export') . '</a>';
            } else {
                echo '-';
            }
            echo '  <br>';
            if (key_exists_and_value_not_null($key, $dependencies_data_export_format_links)) {
                echo '  <a href="' . $dependencies_data_export_format_links[$key] . '">' . $Language->getText('project_export_index', 'show_format') . '</a>';
            } else {
                echo '-';
            }
            echo ' </td>';
            echo '</tr>';
            $iu++;
        }


        echo '</TABLE>';

        site_project_footer(array());
        break;
}
