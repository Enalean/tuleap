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

final class AzureADTenantSetupTest extends TestCase
{
    public function testSetupsAreInstantiatedOnlyOnce(): void
    {
        $known_setups = [
            AzureADTenantSetup::tenantSpecific(),
            AzureADTenantSetup::organizations(),
            AzureADTenantSetup::consumers(),
            AzureADTenantSetup::common()
        ];
        $all_setups   = AzureADTenantSetup::allPossibleSetups();

        sort($known_setups);
        sort($all_setups);

        $this->assertSame($known_setups, $all_setups);

        foreach ($all_setups as $setup) {
            $setup_from_identifier = AzureADTenantSetup::fromIdentifier($setup->getIdentifier());

            $this->assertSame($setup, $setup_from_identifier);
        }
    }

    /**
     * @dataProvider dataProviderAllPossibleSetups
     */
    public function testSetupHaveAnIdentifier(AzureADTenantSetup $setup): void
    {
        $this->assertNotEmpty($setup->getIdentifier());
    }

    /**
     * @dataProvider dataProviderAllPossibleSetups
     */
    public function testSetupHaveADescription(AzureADTenantSetup $setup): void
    {
        $this->assertNotEmpty($setup->getDescription());
    }

    public function dataProviderAllPossibleSetups(): \Generator
    {
        foreach (AzureADTenantSetup::allPossibleSetups() as $setup) {
            yield [$setup];
        }
    }
}
