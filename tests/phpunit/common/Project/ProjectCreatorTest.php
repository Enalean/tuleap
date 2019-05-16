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

namespace Tuleap\Project;

use Backend;
use BackendCVS;
use BackendSVN;
use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectCreator;
use ProjectManager;
use ReferenceManager;
use SystemEventManager;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Service\ServiceCreator;
use UserManager;

/**
 * @see tests/simpletest/common/Project/ProjectCreatorTest.php
 */
final class ProjectCreatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function tearDown() : void
    {
        ProjectManager::clearInstance();
        UserManager::clearInstance();
        SystemEventManager::clearInstance();
        Backend::clearInstances();
    }

    public function testInvalidTemplateIDRaisesAnException() : void
    {
        $project_manager = Mockery::mock(ProjectManager::class);
        ProjectManager::setInstance($project_manager);
        $user_manager    = Mockery::mock(UserManager::class);
        UserManager::setInstance($user_manager);
        $creator         = new ProjectCreator(
            $project_manager,
            Mockery::mock(ReferenceManager::class),
            $user_manager,
            Mockery::mock(UgroupDuplicator::class),
            false,
            Mockery::mock(FRSPermissionCreator::class),
            Mockery::mock(ProjectDashboardDuplicator::class),
            Mockery::mock(ServiceCreator::class),
            Mockery::mock(LabelDao::class),
            new DefaultProjectVisibilityRetriever()
        );

        $project_manager->shouldReceive('getProjectByUnixName')->andReturn(null);
        $template_id = 999;
        $template_project = Mockery::mock(Project::class);
        $template_project->shouldReceive('isError')->andReturn(true);
        $project_manager->shouldReceive('getProject')->with($template_id)->andReturn($template_project);

        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(true);
        $user_manager->shouldReceive('getCurrentUser')->andReturn($user);
        $user_manager->shouldReceive('getUserByUserName')->andReturn(null);

        $system_event_manager = Mockery::mock(SystemEventManager::class);
        $system_event_manager->shouldReceive('isUserNameAvailable')->andReturn(true);
        $system_event_manager->shouldReceive('isProjectNameAvailable')->andReturn(true);
        SystemEventManager::setInstance($system_event_manager);

        $backend_svn = Mockery::mock(BackendSVN::class);
        $backend_svn->shouldReceive('isNameAvailable')->andReturn(true);
        Backend::setInstance('SVN', $backend_svn);
        $backend_cvs = Mockery::mock(BackendCVS::class);
        $backend_cvs->shouldReceive('isNameAvailable')->andReturn(true);
        Backend::setInstance('CVS', $backend_cvs);

        $this->expectException(ProjectInvalidTemplateException::class);
        $creator->create('shortname', 'public name', ['project' => ['built_from_template' => $template_id]]);
    }
}
