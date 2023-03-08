<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use phpseclib3\Exception\NoKeyLoadedException;

class Encryption_Manager
{
    /**
     * @var \phpseclib3\Crypt\RSA\PublicKey
     */
    private $public_key;

    public function __construct(Tracker_Key $tracker_key)
    {
        $raw_key = $tracker_key->getKey();
        if ($raw_key === '') {
            self::throwException();
        }
        try {
            $public_key = \phpseclib3\Crypt\PublicKeyLoader::load($tracker_key->getKey());
            assert($public_key instanceof \phpseclib3\Crypt\RSA\PublicKey);
        } catch (NoKeyLoadedException $exception) {
            self::throwException();
        }
        $this->public_key = $public_key;
    }

    /**
     * encrypt a given data using phpseclib
     * @param $data
     *
     * @return string Encrypted key or an error message in case of encryption issues
     */
    public function encrypt($data)
    {
        $encrypted = $this->public_key->encrypt($data);
        if (is_string($encrypted)) {
            return base64_encode($encrypted);
        }
        self::throwException();
    }

    /**
     * @psalm-return never-return
     */
    private static function throwException(): void
    {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, dgettext('tuleap-tracker_encryption', 'Unable to encrypt data. Please add a valid public RSA key in the tracker administration to be able to encrypt data.'));
        throw new Tracker_EncryptionException();
    }
}
