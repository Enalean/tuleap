<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

class b201210111401_add_remote_servers extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
add table plugin_git_remote_servers and field remote_server to table plugin_git
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
        $table = 'CREATE TABLE plugin_git_remote_servers (
                    id INT(11) UNSIGNED NOT NULL auto_increment,
                    host VARCHAR(255) NOT NULL,
                    port INT(11) UNSIGNED NOT NULL,
                    login VARCHAR(255) NOT NULL,
                    identity_file VARCHAR(255) NOT NULL,
                PRIMARY KEY (id))';
        $this->execDB($table, 'An error occured while adding plugin_git_remote_servers : ');

        $foreign_key = 'ALTER TABLE plugin_git
                        ADD COLUMN remote_server_id INT(11) UNSIGNED NULL,
                        ADD FOREIGN KEY remote_server_idx (remote_server_id) REFERENCES plugin_git_remote_servers (id)';
        $this->execDB($foreign_key, 'An error occured while foreign key to plugin_git: ');
    }

    protected function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
