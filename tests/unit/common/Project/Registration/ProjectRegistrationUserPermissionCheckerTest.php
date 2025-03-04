<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace phpunit\common\Project\Registration;

use ForgeConfig;
use Project;
use ProjectDao;
use ProjectManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Registration\AnonymousNotAllowedException;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForPlatformException;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForUserException;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\LimitedToSiteAdministratorsException;
use Tuleap\Project\Registration\RestrictedUsersNotAllowedException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectRegistrationUserPermissionCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectDao
     */
    private $project_dao;
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;

    protected function setUp(): void
    {
        $this->project_dao        = $this->createMock(ProjectDao::class);
        $this->permission_checker = new ProjectRegistrationUserPermissionChecker($this->project_dao);

        $this->user = $this->createMock(\PFUser::class);
        $this->user->method('getId')->willReturn(110);
        $this->user->method('isAnonymous')->willReturn(false);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isRestricted')->willReturn(false);
    }

    public function testItThrowsExceptionWhenPlatformDoesNotAllRegistrationAndUserIsNotGlobalAdmin(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '0');

        $this->expectException(LimitedToSiteAdministratorsException::class);

        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testAnonymousCanNotCreateNewProject(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');

        $anonymous_user = $this->createMock(\PFUser::class);
        $anonymous_user->method('getId')->willReturn(101);
        $anonymous_user->method('isAnonymous')->willReturn(true);
        $anonymous_user->method('isSuperUser')->willReturn(false);
        $anonymous_user->method('isRestricted')->willReturn(false);

        $this->expectException(AnonymousNotAllowedException::class);

        $this->permission_checker->checkUserCreateAProject($anonymous_user);
    }

    public function testUserCanCreateProjectWhenNoRestrictionsAreConfigured(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');

        $this->permission_checker->checkUserCreateAProject($this->user);

        $this->addToAssertionCount(1);
    }

    public function testUserCannotCreateProjectWhenMaxInQueue(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_PROJECT_APPROVAL, '1');
        ForgeConfig::set(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION, '5');

        $this->project_dao->expects(self::once())->method('countByStatus')->with(Project::STATUS_PENDING)->willReturn(5);

        $this->expectException(MaxNumberOfProjectReachedForPlatformException::class);

        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testUserCanCreateProjectWhenQueueNotFull(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_PROJECT_APPROVAL, '1');
        ForgeConfig::set(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION, '5');

        $this->project_dao->expects(self::once())->method('countByStatus')->with(Project::STATUS_PENDING)->willReturn(4);

        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testUserCannotCreateWhenMaxProjectPerUserIsReached(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_PROJECT_APPROVAL, '1');
        ForgeConfig::set(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER, '5');

        $this->project_dao->expects(self::once())->method('countByStatusAndUser')->with(110, Project::STATUS_PENDING)->willReturn(5);

        $this->expectException(MaxNumberOfProjectReachedForUserException::class);
        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testUserCanCreateWhenMaxProjectPerUserIsNotReached(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_PROJECT_APPROVAL, '1');
        ForgeConfig::set(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER, '5');

        $this->project_dao->expects(self::once())->method('countByStatusAndUser')->with(110, Project::STATUS_PENDING)->willReturn(4);

        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testUserCannotCreateProjectBecauseSheIsRestricted(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_RESTRICTED_USERS_CAN_CREATE_PROJECTS, '0');

        $restricted_user = $this->createMock(\PFUser::class);
        $restricted_user->method('getId')->willReturn(101);
        $restricted_user->method('isAnonymous')->willReturn(false);
        $restricted_user->method('isSuperUser')->willReturn(false);
        $restricted_user->method('isRestricted')->willReturn(true);

        $this->expectException(RestrictedUsersNotAllowedException::class);

        $this->permission_checker->checkUserCreateAProject($restricted_user);
    }

    public function testUserCanCreateProjectBecauseDespiteBeenRestricted(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_RESTRICTED_USERS_CAN_CREATE_PROJECTS, '1');

        $restricted_user = $this->createMock(\PFUser::class);
        $restricted_user->method('getId')->willReturn(101);
        $restricted_user->method('isAnonymous')->willReturn(false);
        $restricted_user->method('isSuperUser')->willReturn(false);
        $restricted_user->method('isRestricted')->willReturn(true);

        $this->permission_checker->checkUserCreateAProject($restricted_user);

        $this->addToAssertionCount(1);
    }

    public function testUserCannotCreateProjectBecauseSheIsRestrictedAndThereIsNoConfigurationSet(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');

        $restricted_user = $this->createMock(\PFUser::class);
        $restricted_user->method('getId')->willReturn(101);
        $restricted_user->method('isAnonymous')->willReturn(false);
        $restricted_user->method('isSuperUser')->willReturn(false);
        $restricted_user->method('isRestricted')->willReturn(true);

        $this->expectException(RestrictedUsersNotAllowedException::class);

        $this->permission_checker->checkUserCreateAProject($restricted_user);
    }
}
