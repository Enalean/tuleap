<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

require_once __DIR__ . '/../../src/www/include/pre.php';

$sql = 'SELECT user_id, user_name, realname, email, authorized_keys FROM user WHERE authorized_keys != "" AND authorized_keys IS NOT NULL';
$res = db_query($sql);
while ($row = db_fetch_array($res)) {
    $valid_keys = array();
    $keys = array_filter(explode(PFUser::SSH_KEY_SEPARATOR, $row['authorized_keys']));
    foreach ($keys as $key) {
        $key_file = '/var/tmp/codendi_cache/ssh_key_check';
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
    $str_valid_keys = implode(PFUser::SSH_KEY_SEPARATOR, $valid_keys);
    if ($str_valid_keys !== $row['authorized_keys']) {
        echo "Update user (" . $row['user_id'] . ") " . $row['user_name'] . " " . $row['realname'] . " " . $row['email'] . PHP_EOL;
        $sql = 'UPDATE user SET authorized_keys = "' . db_es($str_valid_keys) . '" WHERE user_id = ' . $row['user_id'];
        db_query($sql);
    }
}
