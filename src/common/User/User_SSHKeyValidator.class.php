<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

/**
 * Ensure SSH key is valid
 */
class User_SSHKeyValidator {

    /**
     * Ensure all the keys for a user are valid SSH keys
     *
     * @param array $all_keys
     *
     * @return Array of String
     */

    public function validateAllKeys(array $all_keys) {
        $valid_keys = array();
        $key_file   = tempnam(ForgeConfig::get('codendi_cache_dir'), 'ssh_key_');
        foreach ($all_keys as $key) {
            $key = trim($key);

            if ($this->isValid($key_file, $key)) {

                if (in_array($key, $valid_keys)) {
                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('account_editsshkeys', 'key_already_added', array($key)));
                } else {
                    $valid_keys[] = $key;
                }

            } else {
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('account_editsshkeys', 'invalid_key', array($key)));
            }
        }
        unlink($key_file);
        return $valid_keys;
    }

    private function isValid($key_file, $key) {
        if ($key === '') {
            return false;
        }
        $written = file_put_contents($key_file, $key);
        if ($written === strlen($key)) {
            $return = 1;
            $output = array();
            exec('ssh-keygen -l -f ' . escapeshellarg($key_file) . ' > /dev/null 2>&1', $output, $return);
            if ($return === 0) {
                return true;
            }
        }
        return false;
    }
}
