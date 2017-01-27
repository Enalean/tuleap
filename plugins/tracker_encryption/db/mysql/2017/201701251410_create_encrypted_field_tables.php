<?php
/**
 * Copyright (c) Enalean 2017. All rights reserved
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

class b201701251410_create_encrypted_field_tables extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Create encrypted field tables";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->createTable();

        $this->db->dbh->beginTransaction();
        $this->migrateExistingData();
        $this->removeOldEntries();
        $this->db->dbh->commit();
    }

    private function createTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS tracker_changeset_value_encrypted (
                changeset_value_id INT(11) NOT NULL,
                value text,
                PRIMARY KEY(changeset_value_id)
            ) ENGINE=InnoDB;
        ";

        $this->db->dbh->exec($sql);
        if (! $this->db->tableNameExists('tracker_changeset_value_encrypted')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occured while creating encrypted field tables'
            );
        }
    }

    private function migrateExistingData()
    {
        $sql = "INSERT IGNORE INTO tracker_changeset_value_encrypted
                SELECT value_text.changeset_value_id, value_text.value
                    FROM tracker_field  AS field
                    INNER JOIN tracker_changeset_value AS value
                        ON field.id = value.field_id
                    INNER JOIN tracker_changeset_value_text  AS value_text
                        ON value.id = value_text.changeset_value_id
                WHERE formElement_type = 'Encrypted'";

        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occured while migrating data'
            );
        }
    }

    private function removeOldEntries()
    {
        $sql = "DELETE value_text FROM tracker_field  AS field
                    INNER JOIN tracker_changeset_value AS value
                        ON field.id = value.field_id
                    INNER JOIN tracker_changeset_value_text  AS value_text
                        ON value.id = value_text.changeset_value_id
                    WHERE formElement_type = 'Encrypted'";

        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occured while removing old data'
            );
        }
    }
}
