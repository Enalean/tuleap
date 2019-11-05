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

declare(strict_types = 1);

namespace phpunit\common\Project\Registration;

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Request\ForbiddenException;

class ProjectRegistrationUserPermissionCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $builder;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager = \Mockery::mock(ProjectManager::class);
        $this->builder = new ProjectRegistrationUserPermissionChecker($this->project_manager);
    }

    public function testItThrowsExceptionWhenPlatformDoesNotAllRegistrationAndUserIsNotGlobalAdmin(): void
    {
        ForgeConfig::set("sys_use_project_registration", false);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->once()->andReturnFalse();

        $request  = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $this->expectException(ForbiddenException::class);

        $this->builder->checkUserCreateAProject($request);
    }

    public function testAnonymousCanNotCreateNewProject(): void
    {
        ForgeConfig::set("sys_use_project_registration", true);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAnonymous')->once()->andReturnTrue();

        $request  = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $this->expectException(ForbiddenException::class);

        $this->builder->checkUserCreateAProject($request);
    }

    public function testUserCanNotCreateProjectRaiseException() : void
    {
        ForgeConfig::set("sys_use_project_registration", true);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAnonymous')->once()->andReturnFalse();
        $this->project_manager->shouldReceive('userCanCreateProject')->withArgs([$user])->once()->andReturnFalse();

        $request  = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $this->expectException(ForbiddenException::class);

        $this->builder->checkUserCreateAProject($request);
    }

    public function testUserCanCreateProject() : void
    {
        ForgeConfig::set("sys_use_project_registration", true);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAnonymous')->once()->andReturnFalse();
        $this->project_manager->shouldReceive('userCanCreateProject')->withArgs([$user])->once()->andReturnTrue();

        $request  = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->once()->andReturn($user);

        $this->builder->checkUserCreateAProject($request);
    }
}
