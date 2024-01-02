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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Project_AccessDeletedException;
use Project_AccessPrivateException;
use Project_AccessProjectNotFoundException;
use Project_AccessRestrictedException;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

class ProjectAccessCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private RestrictedUserCanAccessVerifier&MockObject $verifier;
    private EventManager&MockObject $event_manager;
    private ProjectAccessChecker $checker;

    /**
     * @before
     */
    public function createInstance(): void
    {
        $this->verifier      = $this->createMock(RestrictedUserCanAccessUrlOrProjectVerifier::class);
        $this->event_manager = $this->createMock(EventManager::class);

        $this->checker = new ProjectAccessChecker($this->verifier, $this->event_manager);
    }

    public function testRestrictedUserCanNotAccessProjectWhichDoesntAllowRestricted(): void
    {
        $user = UserTestBuilder::aRestrictedUser()
            ->withoutSiteAdministrator()
            ->withoutMemberOfProjects()
            ->build();

        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccess(Project::ACCESS_PRIVATE_WO_RESTRICTED)
            ->build();

        self::expectException(\Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanAccessProjectWhichAllowsRestricted(): void
    {
        $user = UserTestBuilder::aRestrictedUser()
            ->withoutSiteAdministrator()
            ->withoutMemberOfProjects()
            ->build();

        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccess(Project::ACCESS_PUBLIC_UNRESTRICTED)
            ->build();

        $this->verifier->method('isRestrictedUserAllowedToAccess')->willReturn(true);

        self::expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCannotAccessProjectWhichAllowsRestrictedButVerifierDoesNot(): void
    {
        $user = UserTestBuilder::aRestrictedUser()
            ->withoutSiteAdministrator()
            ->withoutMemberOfProjects()
            ->build();

        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccess(Project::ACCESS_PUBLIC_UNRESTRICTED)
            ->build();

        $this->verifier->method('isRestrictedUserAllowedToAccess')->willReturn(false);

        self::expectException(\Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanNotAccessAProjectMarkedAsPrivateWithoutRestrictedEvenSheIsMemberOf(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $project = ProjectTestBuilder::aProject()
            ->withId(42)
            ->withAccess(Project::ACCESS_PRIVATE_WO_RESTRICTED)
            ->build();

        $user = UserTestBuilder::aRestrictedUser()
            ->withoutSiteAdministrator()
            ->withMemberOf($project)
            ->build();

        self::expectException(Project_AccessProjectNotFoundException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanAccessAProjectMarkedAsPrivateEvenSheIsMemberOf(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $project = ProjectTestBuilder::aProject()
            ->withId(42)
            ->withAccess(Project::ACCESS_PRIVATE)
            ->build();

        $user = UserTestBuilder::aRestrictedUser()
            ->withoutSiteAdministrator()
            ->withMemberOf($project)
            ->build();

        self::expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessToProjectMembers(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PRIVATE)
            ->build();

        $user = UserTestBuilder::anActiveUser()
            ->withoutSiteAdministrator()
            ->withMemberOf($project)
            ->build();

        self::expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessToNonProjectMembersForPublicProjects(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();

        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PUBLIC)
            ->build();

        self::expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToRestrictedUsersNotProjectMembers(): void
    {
        $user = UserTestBuilder::aRestrictedUser()
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();

        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PRIVATE_WO_RESTRICTED)
            ->build();

        self::expectException(Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessToRestrictedUsersThatAreProjectMembers(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PUBLIC)
            ->build();

        $user = UserTestBuilder::aRestrictedUser()
            ->withMemberOf($project)
            ->withoutSiteAdministrator()
            ->build();

        self::expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToActiveUsersThatAreNotPrivateProjectMembers(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();

        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PRIVATE)
            ->build();

        $this->event_manager->method('processEvent');

        self::expectException(Project_AccessPrivateException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsRestrictedUsersToAccessProjectsTheyAreNotMemberOf(): void
    {
        $user = UserTestBuilder::aRestrictedUser()
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();

        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PUBLIC)
            ->build();

        self::expectException(Project_AccessRestrictedException::class);

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToDeletedProjects(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PUBLIC)
            ->withStatusDeleted()
            ->build();

        $user = UserTestBuilder::anActiveUser()
            ->withMemberOf($project)
            ->withoutSiteAdministrator()
            ->build();

        self::expectException(Project_AccessDeletedException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToSuspendedProjects(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PUBLIC)
            ->withStatusSuspended()
            ->build();

        $user = UserTestBuilder::anActiveUser()
            ->withMemberOf($project)
            ->withoutSiteAdministrator()
            ->build();

        self::expectException(ProjectAccessSuspendedException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToNonExistentProject(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(110);
        $project->method('isError')->willReturn(true);
        $project->method('isPublic')->willReturn(true);
        $project->method('isActive')->willReturn(true);

        self::expectException(Project_AccessProjectNotFoundException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItBlindlyGrantAccessForSiteAdmin(): void
    {
        $user = UserTestBuilder::buildSiteAdministrator();

        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PRIVATE)
            ->build();

        self::expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItGrantsAccessForUserWithDelegatedAccessByAPlugin(): void
    {
        $user = UserTestBuilder::anActiveUser()
            ->withoutMemberOfProjects()
            ->withoutSiteAdministrator()
            ->build();

        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PRIVATE)
            ->build();

        $this->event_manager->method('processEvent')->with(
            self::callback(static function (DelegatedUserAccessForProject $event): bool {
                $event->enableAccessToProjectToTheUser();
                return true;
            })
        );

        self::expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToAnonymousUsersWhenTheInstanceDoesNotAllowAnonymousUsers(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $project = ProjectTestBuilder::aProject()->build();

        self::expectException(Project_AccessProjectNotFoundException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessForAnonymousUsersInAPublicProjectWhenTheInstanceAllowsAnonymousUsers(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PUBLIC)
            ->build();

        self::expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }
}
