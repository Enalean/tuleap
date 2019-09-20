<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class b201307311726_add_workflow_trigger_tables extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add tables for workflow trigger';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE tracker_workflow_trigger_rule_static_value (
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    value_id INT(11) NOT NULL,
                    rule_condition VARCHAR(32) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE KEY (value_id)
                ) ENGINE=InnoDB";
        $this->db->createTable('tracker_workflow_trigger_rule_static_value', $sql);

        $sql = "CREATE TABLE tracker_workflow_trigger_rule_trg_field_static_value (
                    rule_id INT(11) UNSIGNED NOT NULL,
                    value_id INT(11) NOT NULL,
                    INDEX idx_rule_value (rule_id, value_id)
                ) ENGINE=InnoDB";
        $this->db->createTable('tracker_workflow_trigger_rule_trg_field_static_value', $sql);
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('tracker_workflow_trigger_rule_static_value')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_workflow_trigger_rule_static_value');
        }

        if (!$this->db->tableNameExists('tracker_workflow_trigger_rule_trg_field_static_value')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_workflow_trigger_rule_trg_field_static_value');
        }
    }
}
