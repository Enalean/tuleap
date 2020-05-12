<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

class b202005121219_create_jira_import_table extends ForgeUpgrade_Bucket //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function description()
    {
        return 'Add plugin_tracker_pending_jira_import table to import jira issues.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_tracker_pending_jira_import (
                id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                created_on INT(11) UNSIGNED NOT NULL,
                project_id INT(11) NOT NULL,
                user_id INT(11) NOT NULL,
                jira_server TEXT NOT NULL,
                jira_user_email TEXT NOT NULL,
                encrypted_jira_token BLOB NOT NULL,
                jira_project_id TEXT NOT NULL,
                jira_issue_type_name TEXT NOT NULL,
                tracker_name TEXT NOT NULL,
                tracker_shortname TEXT NOT NULL,
                tracker_color VARCHAR(64) NOT NULL,
                tracker_description TEXT NOT NULL,
                INDEX idx_project_id(project_id)
            ) ENGINE=InnoDB;';

        $this->db->createTable('plugin_tracker_pending_jira_import', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_tracker_pending_jira_import')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException(
                'plugin_tracker_pending_jira_import table is missing'
            );
        }
    }
}
