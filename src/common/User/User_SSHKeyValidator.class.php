<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Symfony\Component\Process\Process;

/**
 * Ensure SSH key is valid
 */
class User_SSHKeyValidator // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    private const SSH_KEY_FORMAT_REGEX = '/^(?:(?:ssh-(?:rsa|dss|ed25519))|(?:ecdsa-sha2-nistp(?:256|384|521)))\s+[a-zA-Z0-9+\/]+={0,2}\s*/';

    /**
     * Ensure all the keys for a user are valid SSH keys
     *
     * @param array $all_keys
     *
     * @return string[]
     */

    public function validateAllKeys(array $all_keys)
    {
        $valid_keys = array();
        foreach ($all_keys as $key) {
            $key = trim($key);

            if ($this->isValid($key)) {
                if (in_array($key, $valid_keys, true)) {
                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('account_editsshkeys', 'key_already_added', array($key)));
                } else {
                    $valid_keys[] = $key;
                }
            } else {
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('account_editsshkeys', 'invalid_key', array($key)));
            }
        }
        return $valid_keys;
    }

    private function isValid(string $key): bool
    {
        if ($key === '') {
            return false;
        }
        if (preg_match(self::SSH_KEY_FORMAT_REGEX, $key) !== 1) {
            return false;
        }

        $ssh_keygen_process = Process::fromShellCommandline('ssh-keygen -l -f /dev/stdin <<< "$SSH_KEY"');
        $ssh_keygen_process->run(null, ['SSH_KEY' => $key]);
        return $ssh_keygen_process->isSuccessful();
    }
}
