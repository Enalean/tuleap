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

class b201207070819_add_cardwall_on_top_mappings_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add table to store mappings for cardwall columns
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_cardwall_on_top_column(
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    tracker_id INT(11) NOT NULL,
                    label VARCHAR(255) NOT NULL,
                    bg_red TINYINT UNSIGNED NULL,
                    bg_green TINYINT UNSIGNED NULL,
                    bg_blue TINYINT UNSIGNED NULL,
                    INDEX idx_tracker_id(tracker_id)
                )";
        $this->db->createTable('plugin_cardwall_on_top_column', $sql);

        $sql = "CREATE TABLE IF NOT EXISTS plugin_cardwall_on_top_column_mapping_field(
                    cardwall_tracker_id INT(11) NOT NULL,
                    tracker_id INT(11) NOT NULL,
                    field_id INT(11) NULL,
                    PRIMARY KEY idx(cardwall_tracker_id, tracker_id)
                )";
        $this->db->createTable('plugin_cardwall_on_top_column_mapping_field', $sql);

        $sql = "CREATE TABLE IF NOT EXISTS plugin_cardwall_on_top_column_mapping_field_value(
                    cardwall_tracker_id INT(11) NOT NULL,
                    tracker_id INT(11) NOT NULL,
                    field_id INT(11) NULL,
                    value_id INT(11) NOT NULL,
                    column_id INT(11) NOT NULL,
                    PRIMARY KEY idx(cardwall_tracker_id, tracker_id, field_id, value_id)
                )";
        $this->db->createTable('plugin_cardwall_on_top_column_mapping_field_value', $sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_cardwall_on_top_column')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_cardwall_on_top_column table is missing');
        }
        if (! $this->db->tableNameExists('plugin_cardwall_on_top_column_mapping_field')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_cardwall_on_top_column_mapping_field table is missing');
        }
        if (! $this->db->tableNameExists('plugin_cardwall_on_top_column_mapping_field_value')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_cardwall_on_top_column_mapping_field_value table is missing');
        }
    }
}
