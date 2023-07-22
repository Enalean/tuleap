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

use Tuleap\Config\ConfigDao;
use Event;
use EventManager;
use ForgeAccess;
use ForgeAccess_ForgePropertiesManager;
use PermissionsManager;
use ProjectManager;
use Tuleap\FRS\FRSPermissionCreator;

final class ForgePropertiesManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var ForgeAccess_ForgePropertiesManager
     */
    private $forge_properties_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ConfigDao
     */
    private $config_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&EventManager
     */
    private $event_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&FRSPermissionCreator
     */
    private $frs_permissions_manager;

    protected function setUp(): void
    {
        $this->config_dao              = $this->createMock(ConfigDao::class);
        $this->project_manager         = $this->createMock(ProjectManager::class);
        $this->permissions_manager     = $this->createMock(PermissionsManager::class);
        $this->event_manager           = $this->createMock(EventManager::class);
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
        $this->expectException(UnknownForgeAccessValueException::class);

        $this->forge_properties_manager->updateAccess('not_valid_access_value', 'anonymous');
    }

    public function testUpdateFromRestrictedToAnonymous(): void
    {
        $this->config_dao->method('save')->with(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->event_manager->method('processEvent')->with(Event::SITE_ACCESS_CHANGE, ['old_value' => ForgeAccess::ANONYMOUS, 'new_value' => ForgeAccess::RESTRICTED]);

        $this->frs_permissions_manager->method('updateSiteAccess')->with(ForgeAccess::ANONYMOUS);

        $this->permissions_manager->method('disableRestrictedAccess');

        $this->project_manager->expects(self::once())->method('disableAllowRestrictedForAll');
        $this->forge_properties_manager->updateAccess(ForgeAccess::RESTRICTED, ForgeAccess::ANONYMOUS);
    }
}
