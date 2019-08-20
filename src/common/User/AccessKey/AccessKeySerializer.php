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

use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;

final class AccessKeySerializer implements SplitTokenFormatter, SplitTokenIdentifierTranslator
{
    public const PREFIX  = 'tlp-k1-';
    public const PATTERN = '/^' . self::PREFIX . '(?<key_id>\d+)\.(?<verifier>(?:[[:xdigit:]]{2})+)$/';

    public function getIdentifier(SplitToken $token)
    {
        return new ConcealedString(
            self::PREFIX . $token->getID() . '.' . \sodium_bin2hex((string) $token->getVerificationString()->getString())
        );
    }

    public function getSplitToken(ConcealedString $identifier)
    {
        if (preg_match(self::PATTERN, $identifier, $matches) !== 1) {
            throw new InvalidIdentifierFormatException();
        }
        $verification_string = new SplitTokenVerificationString(new ConcealedString(\sodium_hex2bin($matches['verifier'])));
        return new SplitToken((int) $matches['key_id'], $verification_string);
    }
}
