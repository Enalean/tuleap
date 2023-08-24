<?php
/*
* Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
*
* Originally written by Mohamed CHAARI, 2007. STMicroelectronics.
*
* This file is a part of Codendi.
*
* Codendi is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Codendi is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Codendi; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once __DIR__ . '/../stats/source_code_access_utils.php';
require_once __DIR__ . '/project_export_utils.php';


// Export files access logs for this group
function export_file_logs($project, $span, $who)
{
    $eol = "\n";

    $sql_file      = filedownload_logs_extract($project, $span, $who);
    $col_list_file = ['time', 'user', 'email', 'title', 'local_time'];
    $file_title    =  ['time'      => $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'file_download'),
        'user'       => '',
        'email'      => '',
        'title'      => '',
        'local_time' => '',
    ];
    $lbl_list_file = [ 'time'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'time'),
        'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'user'),
        'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'email'),
        'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'file'),
        'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export', 'local_time'),
    ];
    $result_file   = db_query($sql_file);
    $rows_file     = db_numrows($result_file);
    if ($result_file && $rows_file > 0) {
        // Build csv for files access logs
        echo build_csv_header($col_list_file, $file_title) . $eol;
        echo build_csv_header($col_list_file, $lbl_list_file) . $eol;
        while ($arr_file = db_fetch_array($result_file)) {
            prepare_access_logs_record($project->getGroupId(), $arr_file);
            echo build_csv_record($col_list_file, $arr_file) . $eol;
        }
        echo build_csv_header($col_list_file, []) . $eol;
    }

    $eol = "\n";

    $sql      = frs_logs_extract($project, $span, $who);
    $col_list = ['time', 'type', 'user', 'email', 'title', 'local_time'];
    $title    =  ['time'  => $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'frs_actions'),
        'type'       => '',
        'user'       => '',
        'email'      => '',
        'title'      => '',
        'local_time' => '',
    ];
    $lbl_list = ['time'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'time'),
        'type'       => 'Action',
        'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'user'),
        'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'email'),
        'title'      => $GLOBALS['Language']->getOverridableText('project_stats_source_code_access_utils', 'frs_elements'),
        'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export', 'local_time'),
    ];
    $result   = db_query($sql);
    $rows     = db_numrows($result);
    if ($result && $rows > 0) {
        // Build csv for files access logs
        echo build_csv_header($col_list, $title) . $eol;
        echo build_csv_header($col_list, $lbl_list) . $eol;
        while ($arr = db_fetch_array($result)) {
            prepare_access_logs_record($project->getGroupId(), $arr);
            echo build_csv_record($col_list, $arr) . $eol;
        }
        echo build_csv_header($col_list, []) . $eol;
    }
}

// Export svn access logs for this group
function export_svn_logs($project, $span, $who)
{
    $eol = "\n";

    $sql_svn      = svnaccess_logs_extract($project, $span, $who);
    $col_list_svn = ['day', 'user', 'email', 'svn_access_count', 'svn_browse'];
    $svn_title    =  ['day'              => $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'subversion'),
        'user'             => '',
        'email'            => '',
        'svn_access_count' => '',
        'svn_browse'       => '',
    ];
    $lbl_list_svn = [ 'day'              => $GLOBALS['Language']->getText('project_export_access_logs_export', 'time'),
        'user'             => $GLOBALS['Language']->getText('project_export_access_logs_export', 'user'),
        'email'            => $GLOBALS['Language']->getText('project_export_access_logs_export', 'email'),
        'svn_access_count' => $GLOBALS['Language']->getText('project_export_access_logs_export', 'access'),
        'svn_browse'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'file_brows'),
    ];
    $result_svn   = db_query($sql_svn);
    $rows_svn     = db_numrows($result_svn);

    if ($result_svn && $rows_svn > 0) {
    // Build csv for subversion access logs
        echo build_csv_header($col_list_svn, $svn_title) . $eol;
        echo build_csv_header($col_list_svn, $lbl_list_svn) . $eol;
        while ($arr_svn = db_fetch_array($result_svn)) {
            prepare_access_logs_record($project->getGroupId(), $arr_svn);
            echo build_csv_record($col_list_svn, $arr_svn) . $eol;
        }
        echo build_csv_header($col_list_svn, []) . $eol;
    }
}

// Export wiki pages access logs for this group
function export_wiki_pg_logs($project, $span, $who, $sf)
{
    $eol = "\n";

    $sql_wiki_pg      = wiki_logs_extract($project, $span, $who);
    $col_list_wiki_pg = ['time', 'user', 'email', 'title', 'local_time'];
    $wiki_pg_title    =  ['time'       => $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'wiki_access'),
        'user'       => '',
        'email'      => '',
        'title'      => '',
        'local_time' => '',
    ];
    $lbl_list_wiki_pg = [ 'time'        => $GLOBALS['Language']->getText('project_export_access_logs_export', 'time'),
        'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'user'),
        'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'email'),
        'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'page'),
        'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export', 'local_time'),
    ];
    $result_wiki_pg   = db_query($sql_wiki_pg);
    $rows_wiki_pg     = db_numrows($result_wiki_pg);

    if (! $sf) {
        if ($result_wiki_pg && $rows_wiki_pg > 0) {
        // Build csv for wiki pages access logs
            echo build_csv_header($col_list_wiki_pg, $wiki_pg_title) . $eol;
            echo build_csv_header($col_list_wiki_pg, $lbl_list_wiki_pg) . $eol;
            while ($arr_wiki_pg = db_fetch_array($result_wiki_pg)) {
                prepare_access_logs_record($project->getGroupId(), $arr_wiki_pg);
                echo build_csv_record($col_list_wiki_pg, $arr_wiki_pg) . $eol;
            }
            echo build_csv_header($col_list_wiki_pg, []) . $eol;
        }
    } else {
        //to be used in 'Show Format' link
        if ($result_wiki_pg && $rows_wiki_pg > 0) {
            $dsc_list = [ 'time'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'date_desc'),
                'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'user_desc'),
                'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'email_desc'),
                'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'page_desc'),
                'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export', 'local_time_desc'),
            ];
            $record   = pick_a_record_at_random($result_wiki_pg, $rows_wiki_pg, $col_list_wiki_pg);
            prepare_access_logs_record($project->getGroupId(), $record);
            display_exported_fields($col_list_wiki_pg, $lbl_list_wiki_pg, $dsc_list, $record);
        }
    }
}

// Export wiki pages attachments access logs for this group
function export_wiki_att_logs($project, $span, $who)
{
    $eol = "\n";

    $sql_wiki_att      = wiki_attachments_logs_extract($project, $span, $who);
    $col_list_wiki_att = ['time', 'user', 'email', 'title', 'local_time'];
    $wiki_att_title    =  ['time'       => $GLOBALS['Language']->getText('project_stats_source_code_access_utils', 'wiki_attachments'),
        'user'       => '',
        'email'      => '',
        'title'      => '',
        'local_time' => '',
    ];
    $lbl_list_wiki_att = [ 'time'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'time'),
        'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'user'),
        'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'email'),
        'title'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'attachment'),
        'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export', 'local_time'),
    ];
    $result_wiki_att   = db_query($sql_wiki_att);
    $rows_wiki_att     = db_numrows($result_wiki_att);

    if ($result_wiki_att && $rows_wiki_att > 0) {
        // Build csv for wiki attachments access logs
        echo build_csv_header($col_list_wiki_att, $wiki_att_title) . $eol;
        echo build_csv_header($col_list_wiki_att, $lbl_list_wiki_att) . $eol;
        while ($arr_wiki_att = db_fetch_array($result_wiki_att)) {
            prepare_access_logs_record($project->getGroupId(), $arr_wiki_att);
            echo build_csv_record($col_list_wiki_att, $arr_wiki_att) . $eol;
        }
        echo build_csv_header($col_list_wiki_att, []) . $eol;
    }
}

/**
 * @param $project Project
 * @param $span    Integer
 * @param $who     Integer
 */
function export_all_plugins_logs($project, $span, $who)
{
    $logs = plugins_log_extract($project, $span, $who);
    foreach ($logs as $log) {
        export_plugin_logs($log, $project);
    }
}

/**
 * @param $log     Array
 * @param $project Project
 */
function export_plugin_logs($log, $project)
{
    $eol    = "\n";
    $result = db_query($log['sql']);
    $rows   = db_numrows($result);
    if ($result && $rows > 0) {
        $arr = db_fetch_array($result);

        if (isset($arr['type'])) {
            $col_list     = ['time', 'type', 'user', 'email', 'title', 'local_time'];
            $plugin_title =  ['time'         => $log['title'],
                'type'       => '',
                'user'       => '',
                'email'      => '',
                'title'      => '',
                'local_time' => '',
            ];
            $lbl_list     = [ 'time'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'time'),
                'type'       => $GLOBALS['Language']->getText('project_admin_utils', 'action'),
                'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'user'),
                'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'email'),
                'title'      => $log['field'],
                'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export', 'local_time'),
            ];
        } else {
            $col_list     = ['time', 'user', 'email', 'title', 'local_time'];
            $plugin_title =  ['time'         => $log['title'],
                'user'       => '',
                'email'      => '',
                'title'      => '',
                'local_time' => '',
            ];
            $lbl_list     = [ 'time'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'time'),
                'user'       => $GLOBALS['Language']->getText('project_export_access_logs_export', 'user'),
                'email'      => $GLOBALS['Language']->getText('project_export_access_logs_export', 'email'),
                'title'      => $log['field'],
                'local_time' => $GLOBALS['Language']->getText('project_export_access_logs_export', 'local_time'),
            ];
        }
        // Build csv for plugins logs
        echo build_csv_header($col_list, $plugin_title) . $eol;
        echo build_csv_header($col_list, $lbl_list) . $eol;
        do {
            prepare_access_logs_record($project->getGroupId(), $arr);
            echo build_csv_record($col_list, $arr) . $eol;
        } while ($arr = db_fetch_array($result));
        echo build_csv_header($col_list, []) . $eol;
    }
}
