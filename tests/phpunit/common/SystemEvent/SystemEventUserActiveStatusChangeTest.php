<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\SystemEvent;

use Backend;
use BackendSystem;
use ForgeAccess;
use ForgeConfig;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use TestHelper;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\UserRemover;
use UserGroupDao;
use UserManager;

final class SystemEventUserActiveStatusChangeTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    protected function tearDown(): void
    {
        Backend::clearInstances();
    }

    public function testUserBecomingRestrictedIsRemovedFromProjectNotIncludingRestricted(): void
    {
        $user_id = 102;

        $system_event = new SystemEventUserActiveStatusChange(
            1,
            SystemEventUserActiveStatusChange::TYPE_PROJECT_IS_PRIVATE,
            SystemEventUserActiveStatusChange::APP_OWNER_QUEUE,
            $user_id,
            SystemEventUserActiveStatusChange::PRIORITY_MEDIUM,
            SystemEventUserActiveStatusChange::STATUS_NEW,
            '',
            '',
            '',
            ''
        );

        $user_manager   = Mockery::mock(UserManager::class);
        $user_group_dao = Mockery::mock(UserGroupDao::class);
        $user_remover   = Mockery::mock(UserRemover::class);

        $system_event->injectDependencies($user_manager, $user_group_dao, $user_remover);

        $backend_system = Mockery::mock(BackendSystem::class);
        $backend_system->shouldReceive('flushNscdAndFsCache')->once();
        $backend_system->shouldReceive('createUserHome')->andReturn(true)->once();
        Backend::setInstance('System', $backend_system);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $user = Mockery::mock(PFUser::class);
        $user_manager->shouldReceive('getUserById')->andReturn($user);
        $user->shouldReceive('getId')->andReturn($user_id);
        $user->shouldReceive('isAnonymous')->andReturn(false);
        $user->shouldReceive('isRestricted')->andReturn(true);

        $user_group_dao->shouldReceive('searchActiveProjectsByUserIdAndAccessType')->andReturn(
            TestHelper::arrayToDar(['group_id' => '400'])
        );

        $user_remover->shouldReceive('removeUserFromProject')->once();

        $this->assertTrue($system_event->process());
    }
}
