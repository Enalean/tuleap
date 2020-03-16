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

class b201402130957_add_ugroup_mapping_table extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add plugin_mediawiki_ugroup_mapping table
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
        $sql = "CREATE TABLE IF NOT EXISTS plugin_mediawiki_ugroup_mapping (
            group_id  INT(11) UNSIGNED NOT NULL,
            ugroup_id INT(11) NOT NULL,
            mw_group_name ENUM( 'anonymous', 'user', 'bot', 'sysop', 'bureaucrat' ) NOT NULL DEFAULT 'anonymous'
        )";
        $this->execDB($sql, 'An error occured while adding plugin_mediawiki_ugroup_mapping table: ');
    }

    private function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
