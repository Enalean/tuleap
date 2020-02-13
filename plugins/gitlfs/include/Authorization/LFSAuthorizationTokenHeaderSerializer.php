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

namespace Tuleap\GitLFS\Authorization;

use Tuleap\Authentication\SplitToken\IncorrectSizeVerificationStringException;
use Tuleap\Authentication\SplitToken\InvalidIdentifierFormatException;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenFormatter;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;

final class LFSAuthorizationTokenHeaderSerializer implements SplitTokenFormatter, SplitTokenIdentifierTranslator
{
    public function getIdentifier(SplitToken $token): ConcealedString
    {
        return new ConcealedString(
            'RemoteAuth ' . $token->getID() . '.' . \sodium_bin2hex((string) $token->getVerificationString()->getString())
        );
    }

    /**
     * @throws InvalidIdentifierFormatException
     * @throws IncorrectSizeVerificationStringException
     */
    public function getSplitToken(ConcealedString $identifier): SplitToken
    {
        if (preg_match('/^RemoteAuth (?<id>\d+)\.(?<verifier>(?:[[:xdigit:]]{2})+)$/', $identifier, $matches) !== 1) {
            throw new InvalidIdentifierFormatException();
        }
        $verification_string = new SplitTokenVerificationString(new ConcealedString(\sodium_hex2bin($matches['verifier'])));
        return new SplitToken((int) $matches['id'], $verification_string);
    }
}
