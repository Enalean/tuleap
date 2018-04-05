<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\ForgeAccess;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ForgePropertiesManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @expectedException \Tuleap\ForgeAccess\UnknownForgeAccessValueException
     */
    public function testUnknownAccessValueIsRejected()
    {
        $forge_properties_manager = new \ForgeAccess_ForgePropertiesManager(
            \Mockery::mock(\ConfigDao::class),
            \Mockery::mock(\ProjectManager::class),
            \Mockery::mock(\PermissionsManager::class),
            \Mockery::mock(\EventManager::class),
            \Mockery::mock(\Tuleap\FRS\FRSPermissionCreator::class)
        );

        $forge_properties_manager->updateAccess('not_valid_access_value', 'anonymous');
    }
}
