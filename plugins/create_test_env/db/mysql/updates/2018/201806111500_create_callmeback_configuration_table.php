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

class b201806111500_create_callmeback_configuration_table extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add plugin_callmeback_email and plugin_callmeback_messages to store call me back configutation';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->dbh->beginTransaction();
        $this->createCallMeBackEmailTable();
        $this->createCallMeBackMessagesTable();
        $this->insertCallMeBackMessagesDefaultValues();
        $this->db->dbh->commit();
    }

    private function createCallMeBackEmailTable()
    {
        $sql = "CREATE TABLE plugin_callmeback_email (
          email_to varchar(255) NOT NULL,
          PRIMARY KEY (email_to)
        ) ENGINE=InnoDB;";

        $result = $this->db->createTable('plugin_callmeback_email', $sql);

        if ($result === false) {
            $this->rollBackOnError('Create table plugin_callmeback_email failed');
        }
    }

    private function createCallMeBackMessagesTable()
    {
        $sql = "CREATE TABLE plugin_callmeback_messages (
          language_id varchar(10) NOT NULL,
          message varchar(255),
          PRIMARY KEY (language_id)
        ) ENGINE=InnoDB;";

        $result = $this->db->createTable('plugin_callmeback_messages', $sql);

        if ($result === false) {
            $this->rollBackOnError('Create table plugin_callmeback_messages failed');
        }
    }

    private function insertCallMeBackMessagesDefaultValues()
    {
        $sql = "INSERT INTO plugin_callmeback_messages VALUES
          ('en_US', ''),
          ('fr_FR', '');";
        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $this->rollBackOnError('Insert default plugin_callmeback_messages values failed');
        }
    }
}
