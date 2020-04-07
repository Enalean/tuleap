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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectDao;
use ProjectManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Registration\AnonymousNotAllowedException;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedException;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\LimitedToSiteAdministratorsException;
use Tuleap\Project\Registration\RestrictedUsersNotAllowedException;

class ProjectRegistrationUserPermissionCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectDao
     */
    private $project_dao;
    private $user;

    protected function setUp(): void
    {
        $this->project_dao        = \Mockery::mock(ProjectDao::class);
        $this->permission_checker = new ProjectRegistrationUserPermissionChecker($this->project_dao);

        $this->user = \Mockery::mock(\PFUser::class, ['getId' => '110']);
        $this->user->shouldReceive('isAnonymous')->andReturnFalse()->byDefault();
        $this->user->shouldReceive('isSuperUser')->andReturnFalse()->byDefault();
        $this->user->shouldReceive('isRestricted')->andReturnFalse()->byDefault();
    }

    public function testItThrowsExceptionWhenPlatformDoesNotAllRegistrationAndUserIsNotGlobalAdmin(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '0');

        $this->user->shouldReceive('isSuperUser')->once()->andReturnFalse();

        $this->expectException(LimitedToSiteAdministratorsException::class);

        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testAnonymousCanNotCreateNewProject(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');

        $this->user->shouldReceive('isAnonymous')->once()->andReturnTrue();

        $this->expectException(AnonymousNotAllowedException::class);

        $this->permission_checker->checkUserCreateAProject($this->user);
    }


    public function testUserCanCreateProjectWhenNoRestrictionsAreConfigured(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');

        $this->permission_checker->checkUserCreateAProject($this->user);
    }


    public function testUserCannotCreateProjectWhenMaxInQueue(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_PROJECT_APPROVAL, '1');
        ForgeConfig::set(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION, '5');

        $this->project_dao->shouldReceive('countByStatus')->once()->with(Project::STATUS_PENDING)->andReturn('5');

        $this->expectException(MaxNumberOfProjectReachedException::class);

        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testUserCanCreateProjectWhenQueueNotFull(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_PROJECT_APPROVAL, '1');
        ForgeConfig::set(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION, '5');

        $this->project_dao->shouldReceive('countByStatus')->once()->with(Project::STATUS_PENDING)->andReturn('4');

        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testUserCannotCreateWhenMaxProjectPerUserIsReached(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_PROJECT_APPROVAL, '1');
        ForgeConfig::set(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER, '5');

        $this->project_dao->shouldReceive('countByStatusAndUser')->once()->with(110, Project::STATUS_PENDING)->andReturn(5);

        $this->expectException(MaxNumberOfProjectReachedException::class);
        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testUserCanCreateWhenMaxProjectPerUserIsNotReached(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_PROJECT_APPROVAL, '1');
        ForgeConfig::set(ProjectManager::CONFIG_NB_PROJECTS_WAITING_FOR_VALIDATION_PER_USER, '5');

        $this->project_dao->shouldReceive('countByStatusAndUser')->once()->with(110, Project::STATUS_PENDING)->andReturn(4);

        $this->permission_checker->checkUserCreateAProject($this->user);
    }

    public function testUserCannotCreateProjectBecauseSheIsRestricted(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_RESTRICTED_USERS_CAN_CREATE_PROJECTS, '0');

        $restricted_user = Mockery::mock(PFUser::class, ['isSuperUser' => false, 'isAnonymous' => false, 'isRestricted' => true]);

        $this->expectException(RestrictedUsersNotAllowedException::class);
        $this->permission_checker->checkUserCreateAProject($restricted_user);
    }

    public function testUserCanCreateProjectBecauseDespiteBeenRestricted(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');
        ForgeConfig::set(ProjectManager::CONFIG_RESTRICTED_USERS_CAN_CREATE_PROJECTS, '1');

        $restricted_user = Mockery::mock(PFUser::class, ['isSuperUser' => false, 'isAnonymous' => false, 'isRestricted' => true]);

        $this->permission_checker->checkUserCreateAProject($restricted_user);
    }

    public function testUserCannotCreateProjectBecauseSheIsRestrictedAndThereIsNoConfigurationSet(): void
    {
        ForgeConfig::set(ProjectManager::CONFIG_PROJECTS_CAN_BE_CREATED, '1');

        $restricted_user = Mockery::mock(PFUser::class, ['isSuperUser' => false, 'isAnonymous' => false, 'isRestricted' => true]);

        $this->expectException(RestrictedUsersNotAllowedException::class);

        $this->permission_checker->checkUserCreateAProject($restricted_user);
    }
}
