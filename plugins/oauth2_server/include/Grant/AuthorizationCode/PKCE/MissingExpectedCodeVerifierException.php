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

final class MissingExpectedCodeVerifierException extends \RuntimeException implements OAuth2PKCEVerificationException
{
    public function __construct(OAuth2AuthorizationCode $authorization_code)
    {
        parent::__construct(
            sprintf(
                'Validation of the authorization code #%d expected a PKCE code verifier but none has been provided',
                $authorization_code->getID()
            )
        );
    }
}
