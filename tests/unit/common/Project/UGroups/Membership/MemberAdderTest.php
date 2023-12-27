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

namespace Tuleap\Project\UGroups\Membership;

use ForgeAccess;
use ForgeConfig;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithoutStatusCheckAndNotifications;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberAdder;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class MemberAdderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private MemberAdder $adder;
    private MembershipUpdateVerifier&MockObject $verifier;
    private StaticMemberAdder&MockObject $static_member_adder;
    private DynamicUGroupMembersUpdater&MockObject $dynamic_member_updater;
    private ProjectMemberAdderWithoutStatusCheckAndNotifications&MockObject $project_member_adder;
    private SynchronizedProjectMembershipDetector&MockObject $detector;

    protected function setUp(): void
    {
        $this->verifier = $this->createMock(MembershipUpdateVerifier::class);
        $this->verifier->method('assertUGroupAndUserValidity');
        $this->static_member_adder    = $this->createMock(StaticMemberAdder::class);
        $this->dynamic_member_updater = $this->createMock(DynamicUGroupMembersUpdater::class);
        $this->project_member_adder   = $this->createMock(ProjectMemberAdderWithoutStatusCheckAndNotifications::class);
        $this->detector               = $this->createMock(SynchronizedProjectMembershipDetector::class);
        $this->adder                  = new MemberAdder(
            $this->verifier,
            $this->static_member_adder,
            $this->dynamic_member_updater,
            $this->project_member_adder,
            $this->detector
        );
    }

    public function testAddMemberThrowsWhenProjectExcludesRestrictedAndUserIsRestricted(): void
    {
        $user    = UserTestBuilder::aUser()
            ->withStatus(PFUser::STATUS_RESTRICTED)
            ->withId(217)
            ->build();
        $project = ProjectTestBuilder::aProject()
            ->withAccess(Project::ACCESS_PRIVATE_WO_RESTRICTED)
            ->withId(168)
            ->build();
        $ugroup  = ProjectUGroupTestBuilder::aCustomUserGroup(168)
            ->withProject($project)
            ->build();
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        self::expectException(CannotAddRestrictedUserToProjectNotAllowingRestricted::class);

        $this->adder->addMember($user, $ugroup, UserTestBuilder::buildWithDefaults());
    }

    public function testAddMemberThrowsWhenUGroupHasNoProject(): void
    {
        $user   = UserTestBuilder::buildWithDefaults();
        $ugroup = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getProject')->willReturn(null);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        self::expectException(\UGroup_Invalid_Exception::class);

        $this->adder->addMember($user, $ugroup, UserTestBuilder::buildWithDefaults());
    }

    public function testAddMemberToDynamicUGroupDelegates(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $user    = UserTestBuilder::buildWithDefaults();
        $project = ProjectTestBuilder::aProject()->withAccess(Project::ACCESS_PUBLIC)->build();
        $ugroup  = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('isStatic')->willReturn(false);

        $admin = UserTestBuilder::buildWithDefaults();

        $this->dynamic_member_updater
            ->expects(self::once())
            ->method('addUser')
            ->with($project, $ugroup, $user, $admin);

        $this->adder->addMember($user, $ugroup, $admin);
    }

    public function testAddMemberToStaticUGroupThrowsWhenUGroupDoesNotExist(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $user    = UserTestBuilder::aUser()->withId(217)->build();
        $project = ProjectTestBuilder::aProject()
            ->withId(168)
            ->withAccess(Project::ACCESS_PUBLIC)
            ->build();
        $ugroup  = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('isStatic')->willReturn(true);
        $ugroup->method('getId')->willReturn(24);
        $ugroup->method('exists')->willReturn(false);

        self::expectException(\UGroup_Invalid_Exception::class);

        $this->adder->addMember($user, $ugroup, UserTestBuilder::buildWithDefaults());
    }

    public function testAddMemberToStaticUGroupInNonSynchronizedProjectDoesNotAddToProjectMembers(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $user    = UserTestBuilder::aUser()->withId(217)->build();
        $project = ProjectTestBuilder::aProject()
            ->withId(168)
            ->withAccess(Project::ACCESS_PUBLIC)
            ->build();
        $ugroup  = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('isStatic')->willReturn(true);
        $ugroup->method('getId')->willReturn(24);
        $ugroup->method('exists')->willReturn(true);
        $this->detector
            ->expects(self::once())
            ->method('isSynchronizedWithProjectMembers')
            ->with($project)
            ->willReturn(false);

        $this->static_member_adder
            ->expects(self::once())
            ->method('addUserToStaticGroup')
            ->with(168, 24, 217);
        $this->project_member_adder->expects(self::never())->method('addProjectMemberWithFeedback');

        $this->adder->addMember($user, $ugroup, UserTestBuilder::buildWithDefaults());
    }

    public function testAddMemberToStaticUGroupDoesNotAddToProjectMembersWhenTheyAlreadyAre(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $project = ProjectTestBuilder::aProject()
            ->withId(168)
            ->withAccess(Project::ACCESS_PRIVATE)
            ->build();
        $user    = UserTestBuilder::aUser()
            ->withId(217)
            ->withMemberOf($project)
            ->withoutSiteAdministrator()
            ->build();
        $ugroup  = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('isStatic')->willReturn(true);
        $ugroup->method('getId')->willReturn(24);
        $ugroup->method('exists')->willReturn(true);
        $this->detector
            ->expects(self::once())
            ->method('isSynchronizedWithProjectMembers')
            ->with($project)
            ->willReturn(true);

        $this->static_member_adder
            ->expects(self::once())
            ->method('addUserToStaticGroup')
            ->with(168, 24, 217);
        $this->project_member_adder->expects(self::never())->method('addProjectMemberWithFeedback');

        $this->adder->addMember($user, $ugroup, UserTestBuilder::buildWithDefaults());
    }

    public function testAddMemberToStaticUGroupInSynchronizedProjectAlsoAddsToProjectMembers(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $project = ProjectTestBuilder::aProject()
            ->withId(168)
            ->withAccess(Project::ACCESS_PRIVATE_WO_RESTRICTED)
            ->build();
        $user    = UserTestBuilder::aUser()
            ->withId(217)
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();
        $ugroup  = $this->createMock(ProjectUGroup::class);
        $ugroup->method('getProject')->willReturn($project);
        $ugroup->method('isStatic')->willReturn(true);
        $ugroup->method('getId')->willReturn(24);
        $ugroup->method('exists')->willReturn(true);
        $this->detector
            ->expects(self::once())
            ->method('isSynchronizedWithProjectMembers')
            ->with($project)
            ->willReturn(true);

        $project_admin = UserTestBuilder::buildWithDefaults();

        $this->static_member_adder
            ->expects(self::once())
            ->method('addUserToStaticGroup')
            ->with(168, 24, 217);
        $this->project_member_adder
            ->expects(self::once())
            ->method('addProjectMemberWithFeedback')
            ->with($user, $project, $project_admin);

        $this->adder->addMember($user, $ugroup, $project_admin);
    }
}
