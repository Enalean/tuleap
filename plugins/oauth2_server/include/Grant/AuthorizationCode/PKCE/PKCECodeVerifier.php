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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode\PKCE;

use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCode;

/**
 * @see https://tools.ietf.org/html/rfc7636#section-4.6
 */
class PKCECodeVerifier
{
    /**
     * @throws MissingExpectedCodeVerifierException
     * @throws InvalidFormatCodeVerifierException
     * @throws CodeVerifierDoesNotMatchChallengeException
     */
    public function verifyCode(OAuth2AuthorizationCode $authorization_code, ?string $code_verifier): void
    {
        $code_challenge = $authorization_code->getPKCECodeChallenge();
        if ($code_challenge === null) {
            return;
        }
        if ($code_verifier === null) {
            throw new MissingExpectedCodeVerifierException($authorization_code);
        }

        // See https://tools.ietf.org/html/rfc7636#section-4.2
        if (preg_match('/^[A-Za-z0-9\-\.\_\~]{43,128}$/', $code_verifier) !== 1) {
            throw new InvalidFormatCodeVerifierException();
        }

        if (! \hash_equals($code_challenge, \hash('sha256', $code_verifier, true))) {
            throw new CodeVerifierDoesNotMatchChallengeException();
        }
    }
}
