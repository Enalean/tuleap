<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication;

use Tuleap\OpenIDConnectClient\Provider\Provider;

/**
 * @template-implements IssuerClaimValidator<\Tuleap\OpenIDConnectClient\Provider\GenericProvider\GenericProvider>
 */
final class GenericProviderIssuerClaimValidator implements IssuerClaimValidator
{
    public function isIssuerClaimValid(Provider $provider, string $iss_from_id_token): bool
    {
        /*
         * OpenID Connect Core Standard said the issuer identifier must exactly match
         * the iss claim. However, since we do not implement OpenID Connect Discovery
         * the issuer identifier is not obtained so we do the next best things we can
         * do for now: we check if the iss claim is present in the authorization endpoint
         */
        return strpos($provider->getAuthorizationEndpoint(), $iss_from_id_token) !== false;
    }
}
