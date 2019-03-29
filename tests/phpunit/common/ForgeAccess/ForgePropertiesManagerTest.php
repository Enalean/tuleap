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

use ConfigDao;
use Event;
use EventManager;
use ForgeAccess;
use ForgeAccess_ForgePropertiesManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PermissionsManager;
use PHPUnit\Framework\TestCase;
use ProjectManager;
use Tuleap\FRS\FRSPermissionCreator;

class ForgePropertiesManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ForgeAccess_ForgePropertiesManager
     */
    private $forge_properties_manager;
    /**
     * @var MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var MockInterface|ConfigDao
     */
    private $config_dao;
    /**
     * @var MockInterface|PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var MockInterface|EventManager
     */
    private $event_manager;
    /**
     * @var MockInterface|FRSPermissionCreator
     */
    private $frs_permissions_manager;

    protected function setUp(): void
    {
        $this->config_dao              = Mockery::mock(ConfigDao::class);
        $this->project_manager         = Mockery::mock(ProjectManager::class);
        $this->permissions_manager     = Mockery::mock(PermissionsManager::class);
        $this->event_manager           = Mockery::mock(EventManager::class);
        $this->frs_permissions_manager = Mockery::mock(FRSPermissionCreator::class);

        $this->forge_properties_manager = new ForgeAccess_ForgePropertiesManager(
            $this->config_dao,
            $this->project_manager,
            $this->permissions_manager,
            $this->event_manager,
            $this->frs_permissions_manager
        );
    }

    public function testUnknownAccessValueIsRejected()
    {
        $this->expectException(UnknownForgeAccessValueException::class);

        $this->forge_properties_manager->updateAccess('not_valid_access_value', 'anonymous');
    }

    public function testUpdateFromRestrictedToAnonymous()
    {
        $this->config_dao->shouldReceive('save')->with(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->event_manager->shouldReceive('processEvent')->with(Event::SITE_ACCESS_CHANGE, ['old_value' => ForgeAccess::ANONYMOUS, 'new_value' => ForgeAccess::RESTRICTED]);

        $this->frs_permissions_manager->shouldReceive('updateSiteAccess')->with(ForgeAccess::ANONYMOUS);

        $this->permissions_manager->shouldReceive('disableRestrictedAccess');

        $this->project_manager->shouldReceive('disableAllowRestrictedForAll')->once();
        $this->forge_properties_manager->updateAccess(ForgeAccess::RESTRICTED, ForgeAccess::ANONYMOUS);
    }
}
