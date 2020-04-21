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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectUGroup;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithoutStatusCheckAndNotifications;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberAdder;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;

final class MemberAdderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var MemberAdder
     */
    private $adder;
    /**
     * @var Mockery\MockInterface|MembershipUpdateVerifier
     */
    private $verifier;
    /**
     * @var Mockery\MockInterface|StaticMemberAdder
     */
    private $static_member_adder;
    /**
     * @var Mockery\MockInterface|DynamicUGroupMembersUpdater
     */
    private $dynamic_member_updater;
    /**
     * @var Mockery\MockInterface|ProjectMemberAdderWithoutStatusCheckAndNotifications
     */
    private $project_member_adder;
    /**
     * @var Mockery\MockInterface|SynchronizedProjectMembershipDetector
     */
    private $detector;

    protected function setUp(): void
    {
        $this->verifier = Mockery::mock(MembershipUpdateVerifier::class);
        $this->verifier->shouldReceive('assertUGroupAndUserValidity')->andReturnNull();
        $this->static_member_adder    = Mockery::mock(StaticMemberAdder::class);
        $this->dynamic_member_updater = Mockery::mock(DynamicUGroupMembersUpdater::class);
        $this->project_member_adder   = Mockery::mock(ProjectMemberAdderWithoutStatusCheckAndNotifications::class);
        $this->detector = Mockery::mock(SynchronizedProjectMembershipDetector::class);
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
        $user    = Mockery::mock(PFUser::class, ['isRestricted' => true, 'getId' => 217]);
        $project = Mockery::mock(
            Project::class,
            ['getAccess' => Project::ACCESS_PRIVATE_WO_RESTRICTED, 'getID' => 168]
        );
        $ugroup  = Mockery::mock(ProjectUGroup::class, ['getProject' => $project]);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->expectException(CannotAddRestrictedUserToProjectNotAllowingRestricted::class);

        $this->adder->addMember($user, $ugroup);
    }

    public function testAddMemberThrowsWhenUGroupHasNoProject(): void
    {
        $user   = Mockery::mock(PFUser::class, ['isRestricted' => false]);
        $ugroup = Mockery::mock(ProjectUGroup::class, ['getProject' => null]);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->expectException(\UGroup_Invalid_Exception::class);

        $this->adder->addMember($user, $ugroup);
    }

    public function testAddMemberToDynamicUGroupDelegates(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $user    = Mockery::mock(PFUser::class, ['isRestricted' => false]);
        $project = Mockery::mock(Project::class, ['getAccess' => Project::ACCESS_PUBLIC]);
        $ugroup  = Mockery::mock(ProjectUGroup::class, ['getProject' => $project, 'isStatic' => false]);

        $this->dynamic_member_updater
            ->shouldReceive('addUser')
            ->with($project, $ugroup, $user)
            ->once();

        $this->adder->addMember($user, $ugroup);
    }

    public function testAddMemberToStaticUGroupThrowsWhenUGroupDoesNotExist(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $user    = Mockery::mock(PFUser::class, ['isRestricted' => false, 'getId' => 217]);
        $project = Mockery::mock(Project::class, ['getAccess' => Project::ACCESS_PUBLIC, 'getID' => 168]);
        $ugroup  = Mockery::mock(
            ProjectUGroup::class,
            [
                'getProject' => $project,
                'isStatic'   => true,
                'getId'      => 24,
                'exists'     => false
            ]
        );

        $this->expectException(\UGroup_Invalid_Exception::class);

        $this->adder->addMember($user, $ugroup);
    }

    public function testAddMemberToStaticUGroupInNonSynchronizedProjectDoesNotAddToProjectMembers(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $user    = Mockery::mock(PFUser::class, ['isRestricted' => false, 'getId' => 217]);
        $project = Mockery::mock(
            Project::class,
            ['getAccess' => Project::ACCESS_PUBLIC, 'getID' => 168]
        );
        $ugroup  = Mockery::mock(
            ProjectUGroup::class,
            [
                'getProject' => $project,
                'isStatic'   => true,
                'getId'      => 24,
                'exists'     => true
            ]
        );
        $this->detector->shouldReceive('isSynchronizedWithProjectMembers')
            ->with($project)
            ->once()
            ->andReturnFalse();

        $this->static_member_adder
            ->shouldReceive('addUserToStaticGroup')
            ->with(168, 24, 217)
            ->once();
        $this->project_member_adder->shouldNotReceive('addProjectMember');

        $this->adder->addMember($user, $ugroup);
    }

    public function testAddMemberToStaticUGroupDoesNotAddToProjectMembersWhenTheyAlreadyAre(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $user    = Mockery::mock(PFUser::class, ['isRestricted' => false, 'getId' => 217]);
        $project = Mockery::mock(
            Project::class,
            ['getAccess' => Project::ACCESS_PRIVATE, 'getID' => 168]
        );
        $user->shouldReceive('isMember')
            ->with(168)
            ->once()
            ->andReturnTrue();
        $ugroup  = Mockery::mock(
            ProjectUGroup::class,
            [
                'getProject' => $project,
                'isStatic'   => true,
                'getId'      => 24,
                'exists'     => true
            ]
        );
        $this->detector->shouldReceive('isSynchronizedWithProjectMembers')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->static_member_adder
            ->shouldReceive('addUserToStaticGroup')
            ->with(168, 24, 217)
            ->once();
        $this->project_member_adder->shouldNotReceive('addProjectMember');

        $this->adder->addMember($user, $ugroup);
    }

    public function testAddMemberToStaticUGroupInSynchronizedProjectAlsoAddsToProjectMembers(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $user    = Mockery::mock(PFUser::class, ['isRestricted' => false, 'getId' => 217]);
        $project = Mockery::mock(
            Project::class,
            ['getAccess' => Project::ACCESS_PRIVATE_WO_RESTRICTED, 'getID' => 168]
        );
        $user->shouldReceive('isMember')
            ->with(168)
            ->once()
            ->andReturnFalse();
        $ugroup  = Mockery::mock(
            ProjectUGroup::class,
            [
                'getProject' => $project,
                'isStatic'   => true,
                'getId'      => 24,
                'exists'     => true
            ]
        );
        $this->detector->shouldReceive('isSynchronizedWithProjectMembers')
            ->with($project)
            ->once()
            ->andReturnTrue();

        $this->static_member_adder
            ->shouldReceive('addUserToStaticGroup')
            ->with(168, 24, 217)
            ->once();
        $this->project_member_adder
            ->shouldReceive('addProjectMember')
            ->with($user, $project)
            ->once();

        $this->adder->addMember($user, $ugroup);
    }
}
