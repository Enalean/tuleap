<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Authentication\SplitToken;

use Tuleap\Cryptography\ConcealedString;

/**
 * @psalm-immutable
 */
class SplitTokenVerificationString
{
    public const VERIFICATION_STRING_LENGTH = 32;

    /**
     * @var ConcealedString
     */
    private $verification_string;

    /**
     * @throws IncorrectSizeVerificationStringException
     */
    public function __construct(ConcealedString $verification_string)
    {
        $verification_string_size = \mb_strlen($verification_string->getString(), '8bit');
        if ($verification_string_size !== self::VERIFICATION_STRING_LENGTH) {
            throw new IncorrectSizeVerificationStringException(
                self::VERIFICATION_STRING_LENGTH,
                $verification_string_size
            );
        }
        $this->verification_string = $verification_string;
    }

    public static function generateNewSplitTokenVerificationString(): self
    {
        return new self(
            new ConcealedString(
                \random_bytes(self::VERIFICATION_STRING_LENGTH)
            )
        );
    }

    public function getString(): ConcealedString
    {
        return $this->verification_string;
    }
}
