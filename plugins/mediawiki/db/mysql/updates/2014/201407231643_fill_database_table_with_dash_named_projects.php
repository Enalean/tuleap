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

class b201407231643_fill_database_table_with_dash_named_projects extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add projects with a dash in unix_name to plugin_mediawiki_database table
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
        $sql = "DROP INDEX project_id_idx ON plugin_mediawiki_database";

        $this->execDB($sql, 'An error occured while attempting to delete index project_id_idx from plugin_mediawiki_database');

        $sql = "ALTER TABLE plugin_mediawiki_database ADD PRIMARY KEY(project_id)";

        $this->execDB($sql, 'An error occured while attempting to add a primary key to plugin_mediawiki_database');

        $sql = "REPLACE INTO plugin_mediawiki_database (project_id, database_name)
                (SELECT groups.group_id as project_id, SCHEMA_NAME AS database_name
                    FROM INFORMATION_SCHEMA.SCHEMATA AS s
                    JOIN groups ON REPLACE(groups.unix_group_name,'-','_') = SUBSTRING(s.SCHEMA_NAME, 18)
                    WHERE SCHEMA_NAME LIKE 'plugin_mediawiki%'
                    AND groups.unix_group_name LIKE '%-%'
                )";

        $this->execDB($sql, 'An error occured while filling plugin_mediawiki_database table (projects with dashed name): ');
    }

    private function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
