<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use ParagonIE\ConstantTime\Encoding;
use Tuleap\Cryptography\ConcealedString;

class AccessKey
{
    const PREFIX  = 'tlp-k1-';
    const PATTERN = '/' . self::PREFIX . '(?<key_id>\d+)\.(?<verifier>[[:xdigit:]]+)/';

    /**
     * @var int
     */
    private $access_key_id;
    /**
     * @var AccessKeyVerificationString
     */
    private $verification_string;

    public function __construct($access_key_id, AccessKeyVerificationString $verification_string)
    {
        $this->access_key_id       = $access_key_id;
        $this->verification_string = $verification_string;
    }

    /**
     * @return self
     */
    public static function buildFromIdentifier($identifier)
    {
        if (preg_match(self::PATTERN, $identifier, $matches) !== 1) {
            throw new InvalidIdentifierFormatException();
        }
        $verification_string = new AccessKeyVerificationString(
            new ConcealedString(\sodium_hex2bin($matches['verifier']))
        );
        return new self((int) $matches['key_id'], $verification_string);
    }

    /**
     * @return int
     */
    public function getID()
    {
        return $this->access_key_id;
    }

    /**
     * @return AccessKeyVerificationString
     */
    public function getVerificationString()
    {
        return $this->verification_string;
    }

    /**
     * @return ConcealedString
     */
    public function getIdentifier()
    {
        return new ConcealedString(
            self::PREFIX . $this->access_key_id . '.' . \sodium_bin2hex((string) $this->verification_string->getString())
        );
    }
}
