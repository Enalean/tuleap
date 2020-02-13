<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\User\Password\Reset;

use Tuleap\Authentication\SplitToken\IncorrectSizeVerificationStringException;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;

final class ResetTokenSerializer implements SplitTokenFormatter, SplitTokenIdentifierTranslator
{
    public const PARTS_SEPARATOR = '.';

    public function getIdentifier(SplitToken $token): ConcealedString
    {
        return new ConcealedString(
            $token->getID() . self::PARTS_SEPARATOR . \sodium_bin2hex((string) $token->getVerificationString()->getString())
        );
    }

    /**
     * @throws InvalidIdentifierFormatException
     * @throws IncorrectSizeVerificationStringException
     */
    public function getSplitToken(ConcealedString $identifier): SplitToken
    {
        $identifier_parts = explode(self::PARTS_SEPARATOR, $identifier);
        if (count($identifier_parts) !== 2) {
            throw new InvalidIdentifierFormatException();
        }

        [$token_id, $verifier] = $identifier_parts;
        if ((\strlen($verifier) % 2) !== 0) {
            throw new InvalidIdentifierFormatException();
        }
        $verification_string = new SplitTokenVerificationString(new ConcealedString(\sodium_hex2bin($verifier)));

        return new SplitToken((int) $token_id, $verification_string);
    }
}
