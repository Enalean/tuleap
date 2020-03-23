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

namespace Tuleap\OAuth2Server\AuthorizationServer\PKCE;

use Tuleap\OAuth2Server\App\OAuth2App;

class PKCEInformationExtractor
{
    // See https://tools.ietf.org/html/rfc7636#section-4.3
    private const CODE_CHALLENGE_PARAMETER        = 'code_challenge';
    private const CODE_CHALLENGE_METHOD_PARAMETER = 'code_challenge_method';

    private const SUPPORTED_CHALLENGE_METHOD = 'S256';
    private const SIZE_SHA256_CODE_CHALLENGE = 32;

    /**
     * @throws MissingMandatoryCodeChallengeException
     * @throws NotSupportedChallengeMethodException
     * @throws CodeChallengeNotBase64URLEncodedException
     * @throws IncorrectSizeCodeChallengeException
     */
    public function extractCodeChallenge(OAuth2App $app, array $query_params): ?string
    {
        $has_code_challenge = isset($query_params[self::CODE_CHALLENGE_PARAMETER]);
        if (! $has_code_challenge && $app->isUsingPKCE()) {
            throw new MissingMandatoryCodeChallengeException($app);
        }
        if (! $has_code_challenge) {
            return null;
        }

        $challenge_method = $query_params[self::CODE_CHALLENGE_METHOD_PARAMETER] ?? 'plain';
        if ($challenge_method !== self::SUPPORTED_CHALLENGE_METHOD) {
            throw new NotSupportedChallengeMethodException($challenge_method);
        }

        try {
            $decoded_challenge = sodium_base642bin($query_params[self::CODE_CHALLENGE_PARAMETER], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
        } catch (\SodiumException $exception) {
            throw new CodeChallengeNotBase64URLEncodedException();
        }
        if (mb_strlen($decoded_challenge, '8bit') !== self::SIZE_SHA256_CODE_CHALLENGE) {
            throw new IncorrectSizeCodeChallengeException();
        }

        return $decoded_challenge;
    }
}
