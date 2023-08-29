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
use ForgeAccess;
use ForgeConfig;
use PFUser;
use TestHelper;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\UserRemover;
use Tuleap\Test\Builders\UserTestBuilder;
use UserGroupDao;
use UserManager;

final class SystemEventUserActiveStatusChangeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private const USER_ID = 102;
    private SystemEventUserActiveStatusChange $system_event;
    /**
     * @var UserManager&\PHPUnit\Framework\MockObject\Stub
     */
    private $user_manager;
    /**
     * @var UserGroupDao&\PHPUnit\Framework\MockObject\Stub
     */
    private $user_group_dao;
    /**
     * @var UserRemover&\PHPUnit\Framework\MockObject\Stub
     */
    private $user_remover;

    protected function setUp(): void
    {
        $this->system_event = new SystemEventUserActiveStatusChange(
            1,
            SystemEventUserActiveStatusChange::TYPE_PROJECT_IS_PRIVATE,
            SystemEventUserActiveStatusChange::APP_OWNER_QUEUE,
            self::USER_ID,
            SystemEventUserActiveStatusChange::PRIORITY_MEDIUM,
            SystemEventUserActiveStatusChange::STATUS_NEW,
            '',
            '',
            '',
            ''
        );

        $this->user_manager   = $this->createStub(UserManager::class);
        $this->user_group_dao = $this->createStub(UserGroupDao::class);
        $this->user_remover   = $this->createStub(UserRemover::class);

        $this->system_event->injectDependencies($this->user_manager, $this->user_group_dao, $this->user_remover);
    }

    protected function tearDown(): void
    {
        Backend::clearInstances();
    }

    public function testUserBecomingRestrictedIsRemovedFromProjectNotIncludingRestricted(): void
    {
        $user = UserTestBuilder::aUser()->withId(self::USER_ID)->withStatus(PFUser::STATUS_RESTRICTED)->build();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->user_manager->method('getUserById')->willReturn($user);

        $this->user_group_dao->method('searchActiveProjectsByUserIdAndAccessType')->willReturn(
            TestHelper::arrayToDar(['group_id' => '400'])
        );

        $this->user_remover->expects(self::once())->method('removeUserFromProject');

        $this->assertTrue($this->system_event->process());
    }
}
