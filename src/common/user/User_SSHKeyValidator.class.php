<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'UserManager.class.php';

/**
 * Ensure SSH key is valid
 */
class User_SSHKeyValidator {

    /**
     * Ensure keys submitted by user through web interface are valid SSH keys
     *
     * @param String $keys
     *
     * @return Array of String
     */
    public function filterValidKeys($keys) {
        $all_keys   = array_map('trim', array_filter(preg_split("%(\r\n|\n)%", $keys)));
        return $this->validateAllKeys($all_keys);
    }

    private function validateAllKeys(array $all_keys) {
        $valid_keys = array();
        $key_file   = tempnam(Config::get('codendi_cache_dir'), 'ssh_key_');
        foreach ($all_keys as $key) {
            if ($this->isValid($key_file, $key)) {
                $valid_keys[] = $key;
            } else {
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('account_editsshkeys', 'invalid_key', array($key)));
            }
        }
        unlink($key_file);
        return $valid_keys;
    }

    private function isValid($key_file, $key) {
        $written = file_put_contents($key_file, $key);
        if ($written === strlen($key)) {
            $return = 1;
            $output = array();
            exec("ssh-keygen -l -f $key_file 2>&1", $output, $return);
            if ($return === 0) {
                return true;
            }
        }
        return false;
    }
}

?>
