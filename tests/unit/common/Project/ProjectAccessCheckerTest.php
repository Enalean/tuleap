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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectAccessCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private RestrictedUserCanAccessVerifier&MockObject $verifier;
    private EventManager&MockObject $event_manager;
    private ProjectAccessChecker $checker;

    #[\PHPUnit\Framework\Attributes\Before]
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

        $this->expectException(\Project_AccessRestrictedException::class);

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

        $this->expectNotToPerformAssertions();

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

        $this->expectException(\Project_AccessRestrictedException::class);

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

        $this->expectException(Project_AccessProjectNotFoundException::class);

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

        $this->expectNotToPerformAssertions();

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

        $this->expectNotToPerformAssertions();

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

        $this->expectNotToPerformAssertions();

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

        $this->expectException(Project_AccessRestrictedException::class);

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

        $this->expectNotToPerformAssertions();

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

        $this->expectException(Project_AccessPrivateException::class);

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

        $this->expectException(Project_AccessRestrictedException::class);

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

        $this->expectException(Project_AccessDeletedException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToNotActiveProjects(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PUBLIC)
            ->withStatusPending()
            ->build();

        $user = UserTestBuilder::anActiveUser()
            ->withMemberOf($project)
            ->withoutSiteAdministrator()
            ->build();

        $this->expectException(AccessNotActiveException::class);
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

        $this->expectException(ProjectAccessSuspendedException::class);
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

        $this->expectException(Project_AccessProjectNotFoundException::class);
        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItBlindlyGrantAccessForSiteAdmin(): void
    {
        $user = UserTestBuilder::buildSiteAdministrator();

        $project = ProjectTestBuilder::aProject()
            ->withId(110)
            ->withAccess(Project::ACCESS_PRIVATE)
            ->build();

        $this->expectNotToPerformAssertions();

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

        $this->expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }

    public function testItForbidsAccessToAnonymousUsersWhenTheInstanceDoesNotAllowAnonymousUsers(): void
    {
        $user = UserTestBuilder::anAnonymousUser()->build();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $project = ProjectTestBuilder::aProject()->build();

        $this->expectException(Project_AccessProjectNotFoundException::class);
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

        $this->expectNotToPerformAssertions();

        $this->checker->checkUserCanAccessProject($user, $project);
    }
}
