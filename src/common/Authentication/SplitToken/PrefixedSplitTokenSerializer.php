<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Authentication\SplitToken;

use Tuleap\Cryptography\ConcealedString;

final class PrefixedSplitTokenSerializer implements SplitTokenFormatter, SplitTokenIdentifierTranslator
{
    /**
     * @var PrefixSplitTokenForSerialization
     */
    private $prefix;

    public function __construct(PrefixSplitTokenForSerialization $prefix)
    {
        $this->prefix = $prefix;
    }

    public function getIdentifier(SplitToken $token): ConcealedString
    {
        return new ConcealedString(
            $this->prefix->getString() . $token->getID() . '.' . \sodium_bin2hex((string) $token->getVerificationString()->getString())
        );
    }

    public function getSplitToken(ConcealedString $identifier): SplitToken
    {
        $raw_identifier = $identifier->getString();
        $match_result   = preg_match($this->buildPattern(), $raw_identifier, $matches);
        \sodium_memzero($raw_identifier);
        if ($match_result !== 1) {
            throw new InvalidIdentifierFormatException();
        }

        $verification_string = new SplitTokenVerificationString(new ConcealedString(\sodium_hex2bin($matches['verifier'])));
        return new SplitToken((int) $matches['key_id'], $verification_string);
    }

    private function buildPattern(): string
    {
        return '/^' . preg_quote($this->prefix->getString(), '/') . '(?<key_id>\d+)\.(?<verifier>(?:[[:xdigit:]]{2})+)$/';
    }
}
