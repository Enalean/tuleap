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

namespace Tuleap\OAuth2Server\E2E\RelyingPartyOIDC;

final class OAuth2TestFlowConstants
{
    public const string REDIRECT_URI       = 'https://oauth2-server-rp-oidc:8443/callback';
    public const string BASE_CLIENT_URI    = 'https://tuleap';
    public const string DISCOVERY_ENDPOINT = self::BASE_CLIENT_URI . '/.well-known/openid-configuration';

    private function __construct()
    {
    }
}
