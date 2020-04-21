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

namespace Tuleap\Project\Admin\ProjectUGroup\SynchronizedProjectMembership;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Request\ProjectRetriever;

final class ActivationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ActivationController */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectAdministratorChecker
     */
    private $administrator_checker;
    /**
     * @var M\MockInterface|SynchronizedProjectMembershipDao
     */
    private $dao;
    /**
     * @var \CSRFSynchronizerToken|M\MockInterface
     */
    private $csrf;
    /**
     * @var M\MockInterface|BaseLayout
     */
    private $layout;
    /**
     * @var \HTTPRequest|M\MockInterface
     */
    private $request;

    protected function setUp(): void
    {
        $this->layout            = M::mock(BaseLayout::class);
        $this->request           = M::mock(\HTTPRequest::class);
        $this->project_retriever = M::mock(ProjectRetriever::class);
        $this->administrator_checker     = M::mock(ProjectAdministratorChecker::class);
        $this->dao               = M::mock(SynchronizedProjectMembershipDao::class);
        $this->csrf              = M::mock(\CSRFSynchronizerToken::class);
        $this->controller        = new ActivationController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->dao,
            $this->csrf
        );
    }

    public function testGetUrl(): void
    {
        $project = M::mock(Project::class);
        $project->shouldReceive('getID')
            ->once()
            ->andReturn('104');

        $this->assertEquals(
            '/project/104/admin/change-synchronized-project-membership',
            ActivationController::getUrl($project)
        );
    }

    public function testProcessEnablesSynchronizedProjectMembership(): void
    {
        $this->csrf->shouldReceive('check')->once();
        $project = M::mock(Project::class, ['isError' => false, 'getID' => '104']);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('104')
            ->once()
            ->andReturn($project);
        $variables = ['id' => '104'];
        $this->request->shouldReceive('get')
            ->with('activation')
            ->once()
            ->andReturn('on');
        $user = M::mock(\PFUser::class);
        $this->request->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($user);
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->with($user, $project)
            ->once();

        $this->dao->shouldReceive('enable')
            ->once();
        $this->dao->shouldNotReceive('disable');
        $this->layout->shouldReceive('redirect')
            ->with('/project/admin/ugroup.php?group_id=104')
            ->once();

        $this->controller->process($this->request, $this->layout, $variables);
    }

    public function testProcessDisablesSynchronizedProjectMembership(): void
    {
        $this->csrf->shouldReceive('check')->once();
        $project = M::mock(Project::class, ['isError' => false, 'getID' => '104']);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('104')
            ->once()
            ->andReturn($project);
        $variables = ['id' => '104'];
        $this->request->shouldReceive('get')
            ->with('activation')
            ->once()
            ->andReturnFalse();
        $user = M::mock(\PFUser::class);
        $this->request->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($user);
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->with($user, $project)
            ->once();

        $this->dao->shouldReceive('disable')
            ->once();
        $this->dao->shouldNotReceive('enable');

        $this->layout->shouldReceive('redirect')
            ->with('/project/admin/ugroup.php?group_id=104')
            ->once();

        $this->controller->process($this->request, $this->layout, $variables);
    }
}
