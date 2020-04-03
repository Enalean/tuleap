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
 * @template-implements IssuerClaimValidator<\Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProvider>
 */
final class AzureProviderIssuerClaimValidator implements IssuerClaimValidator
{
    public function isIssuerClaimValid(Provider $provider, string $iss_from_id_token): bool
    {
        foreach ($provider->getAcceptableIssuerTenantIDs() as $acceptable_issuer_tenant_id) {
            if ($iss_from_id_token === $this->buildIssuerURL($acceptable_issuer_tenant_id)) {
                return true;
            }
        }

        return false;
    }

    private function buildIssuerURL(string $guid): string
    {
        return 'https://login.microsoftonline.com/' . urlencode($guid) . '/v2.0';
    }
}
