<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class b201209121717_turn_tables_innodb extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Turn all tracker tables to innodb';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if ($this->indexNameExists('tracker_fileinfo', 'fltxt')) {
            $sql    = 'ALTER TABLE tracker_fileinfo DROP INDEX fltxt';
            $result = $this->db->dbh->exec($sql);
            if ($result === false) {
                $error_message = implode(', ', $this->db->dbh->errorInfo());
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
            }
        }

        $tables = array(
            'tracker_workflow ',
            'tracker_workflow_transition ',
            'tracker_workflow_transition_postactions_field_date ',
            'tracker_workflow_transition_postactions_field_int ',
            'tracker_workflow_transition_postactions_field_float ',
            'tracker_widget_renderer ',
            'tracker',
            'tracker_field',
            'tracker_field_int',
            'tracker_field_float',
            'tracker_field_text',
            'tracker_field_string',
            'tracker_field_msb',
            'tracker_field_date',
            'tracker_field_list',
            'tracker_field_openlist',
            'tracker_field_computed ',
            'tracker_field_openlist_value',
            'tracker_field_list_bind_users',
            'tracker_field_list_bind_static',
            'tracker_field_list_bind_defaultvalue',
            'tracker_field_list_bind_static_value',
            'tracker_changeset',
            'tracker_changeset_comment',
            'tracker_changeset_value',
            'tracker_changeset_value_file',
            'tracker_changeset_value_int',
            'tracker_changeset_value_float',
            'tracker_changeset_value_text',
            'tracker_changeset_value_date',
            'tracker_changeset_value_list',
            'tracker_changeset_value_openlist',
            'tracker_changeset_value_artifactlink',
            'tracker_changeset_value_permissionsonartifact',
            'tracker_fileinfo',
            'tracker_report',
            'tracker_report_renderer',
            'tracker_report_renderer_table',
            'tracker_report_renderer_table_sort',
            'tracker_report_renderer_table_columns',
            'tracker_report_renderer_table_functions_aggregates',
            'tracker_report_criteria',
            'tracker_report_criteria_date_value',
            'tracker_report_criteria_alphanum_value',
            'tracker_report_criteria_file_value',
            'tracker_report_criteria_list_value',
            'tracker_report_criteria_openlist_value',
            'tracker_report_criteria_permissionsonartifact_value',
            'tracker_field_list_bind_decorator',
            'tracker_artifact',
            'tracker_tooltip',
            'tracker_global_notification',
            'tracker_watcher',
            'tracker_notification_role',
            'tracker_notification_event',
            'tracker_notification',
            'tracker_notification_role_default',
            'tracker_notification_event_default',
            'tracker_canned_response',
            'tracker_staticfield_richtext',
            'tracker_semantic_title ',
            'tracker_semantic_status ',
            'tracker_semantic_contributor ',
            'tracker_perm ',
            'tracker_rule',
            'tracker_hierarchy ',
            'tracker_reminder');
        foreach ($tables as $table) {
            if (!$this->isTableInnoDB($table)) {
                $this->log->info("Convert $table");
                $sql = "ALTER TABLE $table ENGINE = InnoDB";
                $result = $this->db->dbh->exec($sql);

                if ($result === false) {
                    $error_message = implode(', ', $this->db->dbh->errorInfo());
                    throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
                }
            }
        }
    }

    private function isTableInnoDB($table)
    {
        $sql = "SHOW TABLE STATUS WHERE Name = '$table' AND Engine = 'InnoDB'";
        $result = $this->db->dbh->query($sql);
        return ($result->fetch() !== false);
    }

    private function indexNameExists($tableName, $index)
    {
        $sql = 'SHOW INDEX FROM ' . $tableName . ' WHERE Key_name LIKE ' . $this->db->dbh->quote($index);
        $res = $this->db->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        } else {
            return false;
        }
    }
}
