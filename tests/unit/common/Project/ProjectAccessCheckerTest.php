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

use EventManager;
use ForgeAccess;
use ForgeConfig;
use Mockery;
use PFUser;
use Project;
use Project_AccessDeletedException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Project_AccessRestrictedException;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;

class ProjectAccessCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    /**
     * @var Mockery\MockInterface|RestrictedUserCanAccessVerifier
     */
    private $verifier;
    /**
     * @var Mockery\MockInterface|EventManager
     */
    private $event_manager;
    /**
     * @var ProjectAccessChecker
     */
    private $checker;

    /**
     * @before
     */
    public function createInstance(): void
    {
        $this->verifier      = Mockery::mock(RestrictedUserCanAccessUrlOrProjectVerifier::class);
        $this->event_manager = Mockery::mock(EventManager::class);

        $this->checker = new ProjectAccessChecker($this->verifier, $this->event_manager);
    }

    public function testRestrictedUserCanNotAccessProjectWhichDoesntAllowRestricted(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => false,
                'isRestricted' => true,
                'isAnonymous'  => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'isError'          => false,
                'isActive'         => true,
                'allowsRestricted' => false,
                'getId'            => 101,
            ]
        );

        $this->expectException(\Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanAccessProjectWhichAllowsRestricted(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => false,
                'isRestricted' => true,
                'isAnonymous'  => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'isError'          => false,
                'isActive'         => true,
                'allowsRestricted' => true,
                'getId'            => 101,
            ]
        );

        $this->verifier->shouldReceive(['isRestrictedUserAllowedToAccess' => true]);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCannotAccessProjectWhichAllowsRestrictedButVerifierDoesNot(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => false,
                'isRestricted' => true,
                'isAnonymous'  => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'isError'          => false,
                'isActive'         => true,
                'allowsRestricted' => true,
                'getId'            => 101,
            ]
        );

        $this->verifier->shouldReceive(['isRestrictedUserAllowedToAccess' => false]);

        $this->expectException(\Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanNotAccessAProjectMarkedAsPrivateWithoutRestrictedEvenSheIsMemberOf(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'     => 42,
                'isError'   => false,
                'isActive'  => true,
                'getAccess' => Project::ACCESS_PRIVATE_WO_RESTRICTED,
            ]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => true,
                'isRestricted' => true,
                'isAnonymous'  => false,
            ]
        );

        $this->expectException(Project_AccessProjectNotFoundException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanAccessAProjectMarkedAsPrivateEvenSheIsMemberOf(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'     => 42,
                'isError'   => false,
                'isActive'  => true,
                'getAccess' => Project::ACCESS_PRIVATE,
            ]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser'  => false,
                'isMember'     => true,
                'isRestricted' => true,
                'isAnonymous'  => false,
            ]
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessToProjectMembers(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'     => 110,
                'isActive'  => true,
                'isError'   => false,
                'getAccess' => Project::ACCESS_PRIVATE,
            ]
        );

        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'    => true,
                'isSuperUser' => false,
                'isAnonymous'  => false,
            ]
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessToNonProjectMembersForPublicProjects(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'     => false,
                'isSuperUser'  => false,
                'isRestricted' => false,
                'isAnonymous'  => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'    => 110,
                'isPublic' => true,
                'isActive' => true,
                'isError'  => false,
            ]
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToRestrictedUsersNotProjectMembers(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'     => false,
                'isRestricted' => true,
                'isSuperUser'  => false,
                'isAnonymous'  => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'            => 110,
                'isActive'         => true,
                'isError'          => false,
                'allowsRestricted' => false,
            ]
        );

        $this->expectException(Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessToRestrictedUsersThatAreProjectMembers(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'     => true,
                'isRestricted' => true,
                'isSuperUser'  => false,
                'isAnonymous'  => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'     => 110,
                'isActive'  => true,
                'isError'   => false,
                'getAccess' => Project::ACCESS_PUBLIC,
            ]
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToActiveUsersThatAreNotPrivateProjectMembers(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isRestricted' => false,
                'isSuperUser'  => false,
                'isMember'     => false,
                'isAnonymous'  => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'    => 110,
                'isPublic' => false,
                'isActive' => true,
                'isError'  => false,
            ]
        );

        $this->event_manager->shouldReceive('processEvent');

        $this->expectException(Project_AccessPrivateException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsRestrictedUsersToAccessProjectsTheyAreNotMemberOf(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'     => false,
                'isRestricted' => true,
                'isSuperUser'  => false,
                'isAnonymous'  => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'            => 110,
                'isPublic'         => true,
                'isActive'         => true,
                'isError'          => false,
                'allowsRestricted' => false,
            ]
        );

        $this->expectException(Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToDeletedProjects(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'    => true,
                'isSuperUser' => false,
                'isAnonymous'  => false,
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
                'getStatus'   => Project::STATUS_DELETED,
            ]
        );

        $this->expectException(Project_AccessDeletedException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToSuspendedProjects(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isMember'    => true,
                'isSuperUser' => false,
                'isAnonymous'  => false,
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
                'getStatus'   => Project::STATUS_DELETED,
            ]
        );

        $this->expectException(ProjectAccessSuspendedException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToNonExistentProject(): void
    {
        $user = Mockery::mock(PFUser::class);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'    => 110,
                'isError'  => true,
                'isPublic' => true,
                'isActive' => true,
            ]
        );

        $this->expectException(Project_AccessProjectNotFoundException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItBlindlyGrantAccessForSiteAdmin(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isSuperUser' => true,
                'isMember'    => false,
                'isAnonymous' => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'    => 110,
                'isPublic' => false,
                'isActive' => false,
                'isError'  => false,
            ]
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessForUserWithDelegatedAccessByAPlugin(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isRestricted' => false,
                'isSuperUser'  => false,
                'isMember'     => false,
                'isAnonymous'  => false,
            ]
        );

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'    => 110,
                'isPublic' => false,
                'isActive' => true,
                'isError'  => false,
            ]
        );

        $this->event_manager->shouldReceive('processEvent')->withArgs(
            static function (DelegatedUserAccessForProject $event): bool {
                $event->enableAccessToProjectToTheUser();
                return true;
            }
        );

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToAnonymousUsersWhenTheInstanceDoesNotAllowAnonymousUsers(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturn(true);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('isError')->andReturn(false);

        $this->expectException(Project_AccessProjectNotFoundException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessForAnonymousUsersInAPublicProjectWhenTheInstanceAllowsAnonymousUsers(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive(
            [
                'isRestricted' => false,
                'isSuperUser'  => false,
                'isMember'     => false,
                'isAnonymous'  => true,
            ]
        );

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive(
            [
                'getID'     => 110,
                'isPublic'  => true,
                'isActive'  => true,
                'isError'   => false,
            ]
        );
        $this->checker->checkUserCanAccessProject($user, $project);
    }
}
