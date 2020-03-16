<?php
/**
 * Copyright (c) Enalean SAS - 2013. All rights reserved
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

class b201306031352_add_branch_and_type_to_log extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
add columns 'refname' and 'operation_type' to table plugin_git_log
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
        $sql = 'ALTER TABLE plugin_git_log
                ADD COLUMN refname TEXT NULL';
        $this->execDB($sql, 'An error occured while adding refname to plugin_git_log: ');

        $sql = 'ALTER TABLE plugin_git_log
                ADD COLUMN operation_type varchar(64) NULL';
        $this->execDB($sql, 'An error occured while adding operation_type to plugin_git_log: ');

        $sql = 'ALTER TABLE plugin_git_log
                ADD COLUMN refname_type varchar(64) NULL';
        $this->execDB($sql, 'An error occured while adding refname_type to plugin_git_log: ');
    }

    private function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
