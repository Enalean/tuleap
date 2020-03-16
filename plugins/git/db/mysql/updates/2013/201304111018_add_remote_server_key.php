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

class b201304111018_add_remote_server_key extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
add column ssh_key to table plugin_git_remote_servers
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
        $this->addColumn();
        $this->importFileKeys();
    }

    private function addColumn()
    {
        $sql = 'ALTER TABLE plugin_git_remote_servers
                ADD COLUMN ssh_key TEXT NULL';
        $this->execDB($sql, 'An error occured while adding plugin_git_remote_servers to ssh_key: ');
    }

    private function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    private function importFileKeys()
    {
        $update_sql = 'UPDATE plugin_git_remote_servers SET ssh_key = :ssh_key WHERE id = :id';
        $update_stm = $this->db->dbh->prepare($update_sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

        $key_prefix_path = '/var/lib/codendi/gitolite/admin/keydir/forge__gerrit_';
        $sql = 'SELECT * FROM plugin_git_remote_servers';
        foreach ($this->db->dbh->query($sql)->fetchAll() as $row) {
            $key_path = $key_prefix_path . $row['id'] . '@0.pub';
            if (is_file($key_path)) {
                $this->log->info("Import key for server " . $row['id'] . "(" . $key_path . ")");
                $update_stm->execute(array(
                    ':ssh_key' => file_get_contents($key_path),
                    ':id' => $row['id']));
            }
        }
    }
}
