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

namespace Tuleap\OpenIDConnectClient\Provider\AzureADProvider;

use PHPUnit\Framework\TestCase;

final class AcceptableTenantForAuthenticationConfigurationTest extends TestCase
{
    public function testValueUsedByTheAuthenticationFlowIsTheIdentifierWhenUsersOutsideOfTheSpecificTenantCanAuthenticate(): void
    {
        $configuration = AcceptableTenantForAuthenticationConfiguration::fromAcceptableTenantForLoginIdentifierAndTenantID(
            'common',
            'tenant_id'
        );

        $this->assertEquals('common', $configuration->getIdentifier());
        $this->assertEquals($configuration->getIdentifier(), $configuration->getValueForAuthenticationFlow());
    }

    public function testValueUsedByTheAuthenticationFlowIsTheTenantIDWhenOnlyUsersFromTheSpecificTenantCanAuthenticate(): void
    {
        $configuration = AcceptableTenantForAuthenticationConfiguration::fromAcceptableTenantForLoginIdentifierAndTenantID(
            'tenant_specific',
            'tenant_id'
        );

        $this->assertEquals('tenant_specific', $configuration->getIdentifier());
        $this->assertEquals('tenant_id', $configuration->getValueForAuthenticationFlow());
    }

    public function testRejectsUnknownAcceptableTenantForLoginIdentifier(): void
    {
        $this->expectException(UnknownAcceptableTenantForAuthenticationIdentifierException::class);
        AcceptableTenantForAuthenticationConfiguration::fromAcceptableTenantForLoginIdentifierAndTenantID(
            'unknown',
            'tenant_id'
        );
    }

    public function testCanBuildConfigurationOnlyAllowingUsersFromTheSpecificTenantToAuthenticate(): void
    {
        $configuration = AcceptableTenantForAuthenticationConfiguration::fromSpecificTenantID(
            'tenant_id'
        );

        $this->assertEquals('tenant_specific', $configuration->getIdentifier());
    }

    /**
     * @testWith ["tenant_specific"]
     *           ["organizations"]
     */
    public function testAcceptableTenantIssuersIDIsTheTenantIDWhenExpectingUsersFromSpecificTenantOrFromOrganizations(string $login_identifier): void
    {
        $configuration = AcceptableTenantForAuthenticationConfiguration::fromAcceptableTenantForLoginIdentifierAndTenantID(
            $login_identifier,
            'tenant_id'
        );

        $this->assertEquals(['tenant_id'], $configuration->getAcceptableIssuerTenantIDs());
    }

    public function testAcceptableTenantIssuersIDIsEitherTheTenantIDOrAnHardcodedGUIDWhenExpectingAllUsers(): void
    {
        $configuration = AcceptableTenantForAuthenticationConfiguration::fromAcceptableTenantForLoginIdentifierAndTenantID(
            'common',
            'tenant_id'
        );

        $this->assertEquals(['9188040d-6c67-4c5b-b112-36a304b66dad', 'tenant_id'], $configuration->getAcceptableIssuerTenantIDs());
    }

    public function testAcceptableTenantIssuersIDIsTheHardcodedGUIDWhenExpectingOnlyConsumersUsers(): void
    {
        $configuration = AcceptableTenantForAuthenticationConfiguration::fromAcceptableTenantForLoginIdentifierAndTenantID(
            'consumers',
            'tenant_id'
        );

        $this->assertEquals(['9188040d-6c67-4c5b-b112-36a304b66dad'], $configuration->getAcceptableIssuerTenantIDs());
    }
}
