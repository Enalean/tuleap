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

use Tuleap\Cryptography\ConcealedString;

class AccessKeyVerificationString
{
    const VERIFICATION_STRING_LENGTH = 32;

    /**
     * @var ConcealedString
     */
    private $verification_string;

    public function __construct(ConcealedString $verification_string)
    {
        $verification_string_size = strlen($verification_string->getString());
        if ($verification_string_size !== self::VERIFICATION_STRING_LENGTH) {
            throw new IncorrectSizeVerificationStringException(
                self::VERIFICATION_STRING_LENGTH,
                $verification_string_size
            );
        }
        $this->verification_string = $verification_string;
    }

    /**
     * @return self
     */
    public static function generateNewAccessKeyVerificationString()
    {
        return new self(
            new ConcealedString(
                \random_bytes(AccessKeyVerificationString::VERIFICATION_STRING_LENGTH)
            )
        );
    }

    /**
     * @return ConcealedString
     */
    public function getString()
    {
        return $this->verification_string;
    }
}
