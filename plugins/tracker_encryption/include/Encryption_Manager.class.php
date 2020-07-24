<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2016. All Rights Reserved.
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

class Encryption_Manager
{
    public const HASH_FUNCTION = 'sha256';
    public const HLEN = 32;
    private $rsa;

    public function __construct(Tracker_Key $tracker_key)
    {
        $this->rsa = new \phpseclib\Crypt\RSA();
        $this->rsa->setEncryptionMode(\phpseclib\Crypt\RSA::ENCRYPTION_OAEP);
        $this->rsa->setHash(self::HASH_FUNCTION);
        $this->rsa->setMGFHash(self::HASH_FUNCTION);
        $this->loadRSAKey($tracker_key);
    }

    /**
     * encrypt a given data using phpseclib
     * @param $data
     *
     * @return string Encrypted key or an error message in case of encryption issues
     */
    public function encrypt($data)
    {
        if ($encrypted = $this->rsa->encrypt($data)) {
            $data      = base64_encode($encrypted);
            return $data;
        } else {
            throw new Tracker_EncryptionException($GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_encryption', 'Unable to encrypt data. Please add a valid public RSA key in the tracker administration to be able to encrypt data.')));
        }
    }

    private function loadRSAKey(Tracker_Key $tracker_key)
    {
        if (! $this->rsa->loadKey($tracker_key->getKey())) {
            throw new Tracker_EncryptionException($GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker_encryption', 'Unable to encrypt data. Please add a valid public RSA key in the tracker administration to be able to encrypt data.')));
        }
    }
}
