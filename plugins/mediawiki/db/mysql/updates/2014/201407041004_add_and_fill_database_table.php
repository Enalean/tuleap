<?php
/**
 * Copyright (c) Enalean SAS - 2014. All rights reserved
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

class b201407041004_add_and_fill_database_table extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add plugin_mediawiki_database table
EOT;
    }

    /**
     * Get the API
     *
     * @return void
     */
    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Creation of the table
     *
     * @return void
     */
    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS plugin_mediawiki_database (
            project_id INT(11) UNSIGNED NOT NULL,
            database_name VARCHAR(255) NULL,
            INDEX project_id_idx(project_id)
            ) ENGINE=InnoDB";

        $this->execDB($sql, 'An error occured while adding plugin_mediawiki_database table: ');

        $sql = "INSERT INTO plugin_mediawiki_database (project_id, database_name)
                (SELECT groups.group_id as project_id, SCHEMA_NAME AS database_name
                    FROM INFORMATION_SCHEMA.SCHEMATA AS s
                    JOIN groups ON groups.unix_group_name = SUBSTRING(s.SCHEMA_NAME, 18)
                    WHERE SCHEMA_NAME LIKE 'plugin_mediawiki%'
                )";
        $this->execDB($sql, 'An error occured while filling plugin_mediawiki_database table (db with name): ');

        $sql = "INSERT INTO plugin_mediawiki_database (project_id, database_name)
                (SELECT groups.group_id as project_id, SCHEMA_NAME AS database_name
                    FROM INFORMATION_SCHEMA.SCHEMATA AS s
                    JOIN groups ON groups.group_id = SUBSTRING(s.SCHEMA_NAME, 18)
                    WHERE SCHEMA_NAME LIKE 'plugin_mediawiki%'
                )";
        $this->execDB($sql, 'An error occured while filling plugin_mediawiki_database table  (db with id): ');
    }

    private function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
