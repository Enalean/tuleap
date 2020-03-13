<?php
/**
 * Copyright (c) Enalean SAS - 2015. All rights reserved
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

class b201510011503_fill_Mediawiki_version_table extends ForgeUpgrade_Bucket
{

    public const MW_123_VERSION = '1.23';
    public const MW_120_VERSION = '1.20';

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Fill plugin_mediawiki_version table
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
        $projects_table_mapping = array();
        $sql                    = "SELECT * FROM plugin_mediawiki_database";
        $res                    = $this->queryDB($sql, 'An error occured while looking for all mediawiki databases');

        foreach ($res->fetchAll() as $database_mapping) {
            if (isset($database_mapping[0]) && isset($database_mapping[1])) {
                $projects_table_mapping[$database_mapping[0]] = $database_mapping[1];
            }
        }

        $this->fillProjectsMediawikiVersions($projects_table_mapping);
    }

    private function fillProjectsMediawikiVersions(array $projects_table_mapping)
    {
        foreach ($projects_table_mapping as $project_id => $database) {
            $version = $this->getProjectMediawikiVersion($database, $project_id);
            $this->fillProjectMediawikiVersion($project_id, $version);
        }
    }

    private function fillProjectMediawikiVersion($project_id, $version)
    {
        $sql = "REPLACE INTO plugin_mediawiki_version (project_id, mw_version)
                VALUES ($project_id, $version)";

        $this->execDB($sql, "An error occured while trying to insert mediawiki version $version in project $project_id");
    }

    private function getProjectMediawikiVersion($database, $project_id)
    {
        $sql = "SHOW TABLES IN $database LIKE 'mwsites'";
        $res = $this->queryDB($sql, "An error occured while getting mediawiki version of project $project_id");

        if (count($res->fetchAll()) == 0) {
            return self::MW_120_VERSION;
        }

        return self::MW_123_VERSION;
    }

    private function queryDB($sql, $message)
    {
        $res = $this->db->dbh->query($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }

        return $res;
    }

    private function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }

        return $res;
    }
}
