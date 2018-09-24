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

namespace Tuleap\user\AccessKey;

use ParagonIE\ConstantTime\Encoding;
use Tuleap\Cryptography\ConcealedString;

class AccessKey
{
    const VERIFIER_LENGTH = 32;
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
     * @return ConcealedString
     */
    public function getIdentifier()
    {
        return new ConcealedString(
            'tlp-k1-' . $this->access_key_id . '.' . Encoding::hexEncode($this->verification_string->getString())
        );
    }
}
