<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 * Copyright (c) STMicroelectronics, 2016. All Rights Reserved.
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

require_once __DIR__ . '/../../www/project/export/project_export_utils.php';

class Admin_ProjectListExporter
{

    /**
     * @var array
     */
    private $column_list = array('group_id', 'project_name', 'unix_name' ,'status', 'type', 'public', 'members');


    /**
     * Export project list in csv format and define the file size
     *
     * @param String  $group_name_search
     * @param String  $status
     * @param String  $var_return
     *
     */
    public function exportProjectList($group_name_search, $status)
    {
        $dao             = new ProjectDao();
        $result          = $dao->returnAllProjects(0, 0, $status, $group_name_search);
        $projects        = $result['projects'];
        return $this->exportCsv($projects);
    }

    /**
     * Build the header of csv file
     *
     * @return String
     */
    private function buildCsvHeader()
    {
        $csv_header = "";
        $documents_title = array ('group_id'     => $GLOBALS['Language']->getText('admin_grouplist', 'id_group'),
                                  'project_name' => $GLOBALS['Language']->getText('admin_groupedit', 'grp_name'),
                                  'unix_name'    => $GLOBALS['Language']->getText('admin_groupedit', 'unix_grp'),
                                  'status'       => $GLOBALS['Language']->getText('global', 'status'),
                                  'type'         => $GLOBALS['Language']->getText('admin_groupedit', 'group_type'),
                                  'public'       => $GLOBALS['Language']->getText('admin_groupedit', 'public'),
                                  'members'      => $GLOBALS['Language']->getText('admin_grouplist', 'members'));
        $csv_header .= build_csv_header($this->column_list, $documents_title);
        return $csv_header;
    }

    /**
     * Build the body of csv file
     *
     * @param array $projects
     *
     * @return String
     */
    private function buildCsvBody($projects)
    {
        $csv_body = "";
        $daoUsers = new UserGroupDao();
        foreach ($projects as $project) {
            $documents_body = array ('group_id'     => $project['group_id'],
                                     'project_name' => $project['group_name'],
                                     'unix_name'    => $project['unix_group_name'],
                                     'status'       => $this->getProjectStatus($project['status']),
                                     'type'         => $project['type'],
                                     'public'       => $project['access'],
                                     'members'      => $daoUsers->returnUsersNumberByGroupId($project['group_id']));

            $csv_body .= build_csv_record($this->column_list, $documents_body) . "\n";
        }
        return $csv_body;
    }

    /**
     * Export file in csv format
     *
     * @param array $body
     *
     * @return String
     */
    private function exportCsv($body)
    {
        $eol = "\n";
        return $this->buildCsvHeader() . $eol . $this->buildCsvBody($body);
    }

    /**
     * Return project status from status_code
     *
     * @param string $status_code
     *
     * @return String
     */
    private function getProjectStatus($status_code)
    {
        $status = "";
        switch ($status_code) {
            case Project::STATUS_ACTIVE:
                $status = $GLOBALS['Language']->getText('admin_groupedit', 'status_A');
                break;
            case Project::STATUS_PENDING:
                $status = $GLOBALS['Language']->getText('admin_groupedit', 'status_P');
                break;
            case Project::STATUS_SUSPENDED:
                $status = $GLOBALS['Language']->getText('admin_groupedit', 'status_H');
                break;
            case Project::STATUS_DELETED:
                $status = $GLOBALS['Language']->getText('admin_groupedit', 'status_D');
                break;
            case Project::STATUS_SYSTEM:
                $status = $GLOBALS['Language']->getText('admin_groupedit', 'status_s');
                break;
        }
        return $status;
    }
}
