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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
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
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();
        EventManager::clearInstance();
        UserManager::clearInstance();
    }

    public function testRestrictedUsersAreRemovedFromAllUserGroupsWhenProjectBecomesPrivateWithoutRestricted(): void
    {
        $project_id   = 102;
        $system_event = new SystemEvent_PROJECT_IS_PRIVATE(
            1,
            SystemEvent_PROJECT_IS_PRIVATE::TYPE_PROJECT_IS_PRIVATE,
            SystemEvent_PROJECT_IS_PRIVATE::APP_OWNER_QUEUE,
            $project_id . '::1',
            SystemEvent_PROJECT_IS_PRIVATE::PRIORITY_MEDIUM,
            SystemEvent_PROJECT_IS_PRIVATE::STATUS_NEW,
            '',
            '',
            '',
            ''
        );

        $user_remover   = Mockery::mock(UserRemover::class);
        $ugroup_manager = Mockery::mock(\UGroupManager::class);
        $system_event->injectDependencies(
            $user_remover,
            $ugroup_manager
        );

        $project_manager = Mockery::mock(ProjectManager::class);
        ProjectManager::setInstance($project_manager);
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn($project_id);
        $project_manager->shouldReceive('getProject')->andReturn($project);

        $project->shouldReceive('usesCVS')->andReturn(false);
        $project->shouldReceive('usesSVN')->andReturn(false);
        ForgeConfig::set(ProjectVisibilityConfigManager::SEND_MAIL_ON_PROJECT_VISIBILITY_CHANGE, false);
        EventManager::setInstance(Mockery::spy(EventManager::class));

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project->shouldReceive('getAccess')->andReturn(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        $restricted_member = Mockery::mock(PFUser::class);
        $restricted_member->shouldReceive('isRestricted')->andReturn(true);
        $restricted_member_id = 456;
        $restricted_member->shouldReceive('getId')->andReturn($restricted_member_id);
        $member = UserTestBuilder::anActiveUser()->build();
        $project->shouldReceive('getMembers')->andReturn([$restricted_member, $member]);
        $user_remover->shouldReceive('removeUserFromProject')->with($project_id, $restricted_member_id)->once();

        $restricted_user_in_ugroup_only = UserTestBuilder::aRestrictedUser()->build();
        $ugroup_with_restricted         = Mockery::mock(ProjectUGroup::class);
        $ugroup_with_restricted->shouldReceive('getMembers')->andReturn([$restricted_user_in_ugroup_only, $member]);
        $ugroup_with_restricted->shouldReceive('removeUser')->with(
            $restricted_user_in_ugroup_only,
            \Mockery::on(
                function (PFUser $user) {
                    return (int) $user->getId() === 0;
                }
            )
        )->once();
        $ugroup_without_restricted = Mockery::mock(ProjectUGroup::class);
        $ugroup_without_restricted->shouldReceive('getMembers')->andReturn([$member]);
        $ugroup_manager->shouldReceive('getStaticUGroups')->with($project)->andReturn(
            [$ugroup_with_restricted, $ugroup_without_restricted]
        );

        self::assertTrue($system_event->process());
    }
}
