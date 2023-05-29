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

use EventManager;
use ForgeAccess;
use ForgeConfig;
use PFUser;
use Project;
use ProjectManager;
use ProjectUGroup;
use SystemEvent_PROJECT_IS_PRIVATE;
use Tuleap\admin\ProjectCreation\ProjectVisibility\ProjectVisibilityConfigManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\UserRemover;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class SystemEventPROJECTISPRIVATETest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private int $project_id = 102;

    protected function setUp(): void
    {
        parent::setUp();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        ForgeConfig::set(ProjectVisibilityConfigManager::SEND_MAIL_ON_PROJECT_VISIBILITY_CHANGE, false);

        $event_manager = new class extends EventManager {
            public function processEvent($event, $params = [])
            {
                return;
            }
        };
        EventManager::setInstance($event_manager);
    }

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();
        EventManager::clearInstance();
        UserManager::clearInstance();
    }

    public function testRestrictedUsersAreRemovedFromAllUserGroupsWhenProjectBecomesPrivateWithoutRestricted(): void
    {
        $system_event = $this->buildSystemEvent();

        $user_remover   = $this->createMock(UserRemover::class);
        $ugroup_manager = $this->createMock(\UGroupManager::class);
        $system_event->injectDependencies(
            $user_remover,
            $ugroup_manager,
        );

        $project_manager = $this->createMock(ProjectManager::class);
        ProjectManager::setInstance($project_manager);
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn($this->project_id);
        $project_manager->method('getProject')->willReturn($project);

        $project->method('usesCVS')->willReturn(false);
        $project->method('usesSVN')->willReturn(false);

        $project->method('getAccess')->willReturn(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        $restricted_member_id = 456;
        $restricted_admin     = UserTestBuilder::aRestrictedUser()->build();
        $restricted_member    = UserTestBuilder::aRestrictedUser()->withId($restricted_member_id)->build();
        $member               = UserTestBuilder::anActiveUser()->build();

        $project->method('getAdmins')->willReturn([$restricted_admin, $member]);
        $user_remover->expects(self::once())->method('forceRemoveAdminRestrictedUserFromProject')->with($project, $restricted_admin);
        $project->method('getMembers')->willReturn([$restricted_member, $member]);
        $user_remover->expects(self::once())->method('removeUserFromProject')->with($this->project_id, $restricted_member_id);

        $restricted_user_in_ugroup_only = UserTestBuilder::aRestrictedUser()->build();
        $ugroup_with_restricted         = $this->createMock(ProjectUGroup::class);
        $ugroup_with_restricted->method('getMembers')->willReturn([$restricted_user_in_ugroup_only, $member]);
        $ugroup_with_restricted->expects(self::once())->method('removeUser')->with(
            $restricted_user_in_ugroup_only,
            self::callback(
                function (PFUser $user) {
                    return (int) $user->getId() === 0;
                }
            )
        );
        $ugroup_without_restricted = $this->createMock(ProjectUGroup::class);
        $ugroup_without_restricted->method('getMembers')->willReturn([$member]);
        $ugroup_manager->method('getStaticUGroups')->with($project)->willReturn(
            [$ugroup_with_restricted, $ugroup_without_restricted]
        );

        self::assertTrue($system_event->process());
    }

    public function testRestrictedUsersAreRemovedFromAllUserGroupsWhenProjectBecomesPrivateWithoutRestrictedIfAllAdministratorsAreRestricted(): void
    {
        $system_event = $this->buildSystemEvent();

        $user_remover   = $this->createMock(UserRemover::class);
        $ugroup_manager = $this->createMock(\UGroupManager::class);
        $system_event->injectDependencies(
            $user_remover,
            $ugroup_manager,
        );

        $project_manager = $this->createMock(ProjectManager::class);
        ProjectManager::setInstance($project_manager);
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn($this->project_id);
        $project_manager->method('getProject')->willReturn($project);

        $project->method('usesCVS')->willReturn(false);
        $project->method('usesSVN')->willReturn(false);

        $project->method('getAccess')->willReturn(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        $restricted_member_id = 456;
        $restricted_admin     = UserTestBuilder::aRestrictedUser()->build();
        $restricted_member    = UserTestBuilder::aRestrictedUser()->withId($restricted_member_id)->build();
        $member               = UserTestBuilder::anActiveUser()->build();

        $project->method('getAdmins')->willReturn([$restricted_admin]);
        $project->method('getMembers')->willReturn([$restricted_member, $member]);

        $user_remover->expects(self::once())->method('forceRemoveAdminRestrictedUserFromProject')->with($project, $restricted_admin);
        $user_remover->expects(self::once())->method('removeUserFromProject')->with($this->project_id, $restricted_member_id);

        $restricted_user_in_ugroup_only = UserTestBuilder::aRestrictedUser()->build();
        $ugroup_with_restricted         = $this->createMock(ProjectUGroup::class);
        $ugroup_with_restricted->method('getMembers')->willReturn([$restricted_user_in_ugroup_only, $member]);
        $ugroup_with_restricted->expects(self::once())->method('removeUser')->with(
            $restricted_user_in_ugroup_only,
            self::callback(
                function (PFUser $user) {
                    return (int) $user->getId() === 0;
                }
            )
        );
        $ugroup_without_restricted = $this->createMock(ProjectUGroup::class);
        $ugroup_without_restricted->method('getMembers')->willReturn([$member]);
        $ugroup_manager->method('getStaticUGroups')->with($project)->willReturn(
            [$ugroup_with_restricted, $ugroup_without_restricted]
        );

        self::assertTrue($system_event->process());
    }

    private function buildSystemEvent(): SystemEvent_PROJECT_IS_PRIVATE
    {
        return new SystemEvent_PROJECT_IS_PRIVATE(
            1,
            SystemEvent_PROJECT_IS_PRIVATE::TYPE_PROJECT_IS_PRIVATE,
            SystemEvent_PROJECT_IS_PRIVATE::APP_OWNER_QUEUE,
            $this->project_id . '::1',
            SystemEvent_PROJECT_IS_PRIVATE::PRIORITY_MEDIUM,
            SystemEvent_PROJECT_IS_PRIVATE::STATUS_NEW,
            '',
            '',
            '',
            ''
        );
    }
}
