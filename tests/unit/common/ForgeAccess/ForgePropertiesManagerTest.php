<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Config\ConfigDao;
use EventManager;
use ForgeAccess;
use ForgeAccess_ForgePropertiesManager;
use PermissionsManager;
use ProjectManager;
use Tuleap\FRS\FRSPermissionCreator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ForgePropertiesManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ForgeAccess_ForgePropertiesManager $forge_properties_manager;
    private ProjectManager&MockObject $project_manager;
    private ConfigDao&MockObject $config_dao;
    private PermissionsManager&Stub $permissions_manager;
    private EventManager&Stub $event_manager;
    private FRSPermissionCreator&MockObject $frs_permissions_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->config_dao              = $this->createMock(ConfigDao::class);
        $this->project_manager         = $this->createMock(ProjectManager::class);
        $this->permissions_manager     = $this->createStub(PermissionsManager::class);
        $this->event_manager           = $this->createStub(EventManager::class);
        $this->frs_permissions_manager = $this->createMock(FRSPermissionCreator::class);

        $this->forge_properties_manager = new ForgeAccess_ForgePropertiesManager(
            $this->config_dao,
            $this->project_manager,
            $this->permissions_manager,
            $this->event_manager,
            $this->frs_permissions_manager
        );
    }

    public function testUnknownAccessValueIsRejected(): void
    {
        $this->config_dao->expects($this->never())->method('save');
        $this->frs_permissions_manager->expects($this->never())->method('updateSiteAccess');
        $this->project_manager->expects($this->never())->method('disableAllowRestrictedForAll');

        $this->expectException(UnknownForgeAccessValueException::class);

        $this->forge_properties_manager->updateAccess('not_valid_access_value', 'anonymous');
    }

    public function testUpdateFromRestrictedToAnonymous(): void
    {
        $this->config_dao->expects($this->once())->method('save')->with(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->event_manager->method('processEvent');

        $this->frs_permissions_manager->expects($this->once())->method('updateSiteAccess')->with(ForgeAccess::ANONYMOUS);

        $this->permissions_manager->method('disableRestrictedAccess');

        $this->project_manager->expects($this->once())->method('disableAllowRestrictedForAll');
        $this->forge_properties_manager->updateAccess(ForgeAccess::RESTRICTED, ForgeAccess::ANONYMOUS);
    }
}
