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

class b201210121809_purge_bad_sshkeys_from_db extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
Ensure SSH keys uploaded by users are valid.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $update_sql = 'UPDATE user SET authorized_keys = :authorized_keys WHERE user_id = :user_id';
        $update_sth = $this->db->dbh->prepare($update_sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));

        $sql = 'SELECT user_id, user_name, realname, email, authorized_keys FROM user WHERE authorized_keys != "" AND authorized_keys IS NOT NULL';
        $res = $this->db->dbh->query($sql);
        $key_file = '/var/tmp/codendi_cache/ssh_key_check';
        foreach ($res->fetchAll() as $row) {
            $valid_keys = array();
            $keys = array_filter(explode('###', $row['authorized_keys']));
            foreach ($keys as $key) {
                $written  = file_put_contents($key_file, $key);
                if ($written === strlen($key)) {
                    $return = 1;
                    $output = array();
                    exec("ssh-keygen -l -f $key_file 2>&1", $output, $return);
                    if ($return === 0) {
                        $valid_keys[] = $key;
                    }
                }
            }
            $str_valid_keys = implode('###', $valid_keys);
            if ($str_valid_keys !== $row['authorized_keys']) {
                $this->log->info("Remove invalid SSH key for user " . $row['user_id'] . ": " . $row['user_name'] . " (" . $row['realname'] . " <" . $row['email'] . ">)");
                $update_sth->execute(array(':authorized_keys' => $str_valid_keys, ':user_id' => $row['user_id']));
            }
        }
        if (file_exists($key_file)) {
            unlink($key_file);
        }
    }
}
