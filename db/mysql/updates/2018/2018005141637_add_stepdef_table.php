<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class b2018005141637_add_stepdef_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Create plugin_testmanagement_changeset_value_stepdef table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_testmanagement_changeset_value_stepdef(
            id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            changeset_value_id INT(11) NOT NULL,
            description TEXT,
            rank INT(11) UNSIGNED NOT NULL,
            INDEX cvid_idx(changeset_value_id, rank)
        )";
        $result = $this->db->createTable('plugin_testmanagement_changeset_value_stepdef', $sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
