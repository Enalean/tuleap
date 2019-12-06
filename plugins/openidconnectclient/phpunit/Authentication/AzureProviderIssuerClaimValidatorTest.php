<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\OpenIDConnectClient\Authentication;

use PHPUnit\Framework\TestCase;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProvider;

class AzureProviderIssuerClaimValidatorTest extends TestCase
{
    /**
     * @var AzureProviderIssuerClaimValidator
     */
    private $generic_issuer_claim_validator;

    public function setUp(): void
    {
        $this->generic_issuer_claim_validator = new AzureProviderIssuerClaimValidator();
    }

    public function testIssuerClaimIsValid()
    {
        $iss_from_id_token = 'https://login.microsoftonline.com/tenant_id/v2.0';

        $provider = new AzureADProvider(
            0,
            'Provider',
            'client_id',
            'https://example.com/token',
            true,
            'Secret',
            'fiesta_red',
            'tenant_id',
        );

        $result = $this->generic_issuer_claim_validator->isIssuerClaimValid($provider, $iss_from_id_token);

        $this->assertTrue($result);
    }

    public function testIssuerClaimIsInvalid()
    {
        $iss_from_id_token = 'https://login.microsoftonline.com/pas_tenant_id/v2.0';

        $provider = new AzureADProvider(
            0,
            'Provider',
            'client_id',
            'https://example.com/token',
            true,
            'Secret',
            'fiesta_red',
            'tenant_id',
        );

        $result = $this->generic_issuer_claim_validator->isIssuerClaimValid($provider, $iss_from_id_token);

        $this->assertFalse($result);
    }
}
