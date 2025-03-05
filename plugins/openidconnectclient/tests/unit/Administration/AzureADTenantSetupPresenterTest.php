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

namespace Tuleap\OpenIDConnectClient\Administration;

use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADTenantSetup;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AzureADTenantSetupPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildPresentersFromSetOfValues(): void
    {
        $setup = AzureADTenantSetup::common();

        $expected_identifier  = $setup->getIdentifier();
        $expected_description = $setup->getDescription();

        $presenters = AzureADTenantSetupPresenter::fromAllAcceptableValues(
            [AzureADTenantSetup::common(), AzureADTenantSetup::common()],
            AzureADTenantSetup::tenantSpecific()
        );

        $this->assertCount(2, $presenters);
        foreach ($presenters as $presenter) {
            $this->assertEquals($expected_identifier, $presenter->identifier);
            $this->assertEquals($expected_description, $presenter->description);
            $this->assertFalse($presenter->selected);
        }
    }

    public function testMarkPresenterHasSelected(): void
    {
        $presenters = AzureADTenantSetupPresenter::fromAllAcceptableValues(
            [AzureADTenantSetup::organizations()],
            AzureADTenantSetup::organizations()
        );

        $this->assertCount(1, $presenters);
        $this->assertTrue($presenters[0]->selected);
    }
}
