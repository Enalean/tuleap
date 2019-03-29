<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

use ForgeAccess;
use ForgeConfig;
use Mockery;
use PermissionsOverrider_PermissionsOverriderManager;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Project_AccessDeletedException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Project_AccessRestrictedException;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;

class ProjectAccessCheckerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, ForgeConfigSandbox, GlobalLanguageMock;

    /**
     * @var Mockery\MockInterface|PermissionsOverrider_PermissionsOverriderManager
     */
    private $overrider;
    /**
     * @var Mockery\MockInterface|RestrictedUserCanAccessVerifier
     */
    private $verifier;
    /**
     * @var ProjectAccessChecker
     */
    private $checker;

    /**
     * @before
     */
    public function createInstance()
    {
        $this->overrider = Mockery::mock(PermissionsOverrider_PermissionsOverriderManager::class);
        $this->verifier  = Mockery::mock(RestrictedUserCanAccessUrlOrProjectVerifier::class);

        $this->checker = new ProjectAccessChecker($this->overrider, $this->verifier);
    }

    public function testRestrictedUserCanNotAccessProjectWhichDoesntAllowRestricted()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => false,
                'isRestricted' => true
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'isError'          => false,
                'isActive'         => true,
                'allowsRestricted' => false,
                'getId'            => 101
            ]
        );

        $this->overrider->shouldReceive(['doesOverriderAllowUserToAccessProject' => false]);

        $this->expectException(\Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanAccessProjectWhichAllowsRestricted()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => false,
                'isRestricted' => true
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'isError'          => false,
                'isActive'         => true,
                'allowsRestricted' => true,
                'getId'            => 101
            ]
        );

        $this->overrider->shouldReceive(['doesOverriderAllowUserToAccessProject' => false]);
        $this->verifier->shouldReceive(['isRestrictedUserAllowedToAccess' => true]);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCannotAccessProjectWhichAllowsRestrictedButVerifierDoesNot()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => false,
                'isRestricted' => true
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'isError'          => false,
                'isActive'         => true,
                'allowsRestricted' => true,
                'getId'            => 101
            ]
        );

        $this->overrider->shouldReceive(['doesOverriderAllowUserToAccessProject' => false]);
        $this->verifier->shouldReceive(['isRestrictedUserAllowedToAccess' => false]);

        $this->expectException(\Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanNotAccessAProjectMarkedAsPrivateWithoutRestrictedEvenSheIsMemberOf()
    {
        ForgeConfig::set('feature_flag_project_without_restricted', 1);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'     => 42,
                'isError'   => false,
                'isActive'  => true,
                'getAccess' => Project::ACCESS_PRIVATE_WO_RESTRICTED
            ]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => true,
                'isRestricted' => true
            ]
        );

        $this->expectException(Project_AccessProjectNotFoundException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanAccessAProjectMarkedAsPrivateEvenSheIsMemberOf()
    {
        ForgeConfig::set('feature_flag_project_without_restricted', 1);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'     => 42,
                'isError'   => false,
                'isActive'  => true,
                'getAccess' => Project::ACCESS_PRIVATE
            ]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => true,
                'isRestricted' => true
            ]
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessToProjectMembers()
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'     => 110,
                'isActive'  => true,
                'isError'   => false,
                'getAccess' => Project::ACCESS_PRIVATE
            ]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'    => true,
                'isSuperUser' => false
            ]
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessToNonProjectMembersForPublicProjects()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'     => false,
                'isSuperUser'  => false,
                'isRestricted' => false
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'    => 110,
                'isPublic' => true,
                'isActive' => true,
                'isError'  => false
            ]
        );

        $this->overrider->shouldReceive(['doesOverriderAllowUserToAccessProject' => false]);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToRestrictedUsersNotProjectMembers()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'     => false,
                'isRestricted' => true,
                'isSuperUser'  => false
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'            => 110,
                'isActive'         => true,
                'isError'          => false,
                'allowsRestricted' => false
            ]
        );

        $this->overrider->shouldReceive(['doesOverriderAllowUserToAccessProject' => false]);

        $this->expectException(Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessToRestrictedUsersThatAreProjectMembers()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'     => true,
                'isRestricted' => true,
                'isSuperUser'  => false
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'     => 110,
                'isActive'  => true,
                'isError'   => false,
                'getAccess' => Project::ACCESS_PUBLIC
            ]
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToActiveUsersThatAreNotPrivateProjectMembers()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isRestricted' => false,
                'isSuperUser'  => false,
                'isMember'     => false
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'    => 110,
                'isPublic' => false,
                'isActive' => true,
                'isError'  => false
            ]
        );

        $this->overrider->shouldReceive(['doesOverriderAllowUserToAccessProject' => false]);

        $this->expectException(Project_AccessPrivateException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsRestrictedUsersToAccessProjectsTheyAreNotMemberOf()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'     => false,
                'isRestricted' => true,
                'isSuperUser'  => false
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'            => 110,
                'isPublic'         => true,
                'isActive'         => true,
                'isError'          => false,
                'allowsRestricted' => false
            ]
        );

        $this->overrider->shouldReceive(['doesOverriderAllowUserToAccessProject' => false]);

        $this->expectException(Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItAllowsRestrictedUsersToAccessProjectsTheyAreNotMemberOfButOverriderAllowsTo()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'     => false,
                'isRestricted' => true,
                'isSuperUser'  => false
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'            => 110,
                'isPublic'         => true,
                'isActive'         => true,
                'isError'          => false,
                'allowsRestricted' => false
            ]
        );

        $this->overrider->shouldReceive(['doesOverriderAllowUserToAccessProject' => true]);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToDeletedProjects()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'    => true,
                'isSuperUser' => false
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'       => 110,
                'isPublic'    => true,
                'isActive'    => false,
                'isError'     => false,
                'isSuspended' => false,
                'getStatus'   => Project::STATUS_DELETED
            ]
        );

        $this->expectException(Project_AccessDeletedException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToSuspendedProjects()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'    => true,
                'isSuperUser' => false
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'       => 110,
                'isPublic'    => true,
                'isActive'    => false,
                'isError'     => false,
                'isSuspended' => true,
                'getStatus'   => Project::STATUS_DELETED
            ]
        );

        $this->expectException(ProjectAccessSuspendedException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToNonExistantProject()
    {
        $user = Mockery::mock(PFUser::class);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'    => 110,
                'isError'  => true,
                'isPublic' => true,
                'isActive' => true
            ]
        );

        $this->expectException(Project_AccessProjectNotFoundException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItBlindlyGrantAccessForSiteAdmin()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser' => true,
                'isMember'    => false
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'    => 110,
                'isPublic' => false,
                'isActive' => false,
                'isError'  => false
            ]
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }
}
