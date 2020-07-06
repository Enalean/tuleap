<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin;

use EventManager;
use Feedback;
use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use SystemEventManager;
use Tuleap\admin\ProjectEdit\ProjectEditController;
use Tuleap\admin\ProjectEdit\ProjectEditDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;

class ProjectEditControllerTests extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectDetailsPresenter
     */
    private $details_presenter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectEditDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SystemEventManager
     */
    private $system_event_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var ProjectEditController
     */
    private $project_edit_controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->project              = Mockery::mock(Project::class);
        $this->project->shouldReceive('getId')->andReturn(111);

        $this->details_presenter    = Mockery::mock(ProjectDetailsPresenter::class);
        $this->dao                  = Mockery::mock(ProjectEditDao::class);
        $this->project_manager      = Mockery::mock(ProjectManager::class);
        $this->event_manager        = Mockery::mock(EventManager::class);
        $this->system_event_manager = Mockery::mock(SystemEventManager::class);
        $this->project_history_dao  = Mockery::mock(ProjectHistoryDao::class);

        $this->project_edit_controller = new ProjectEditController(
            $this->details_presenter,
            $this->dao,
            $this->project_manager,
            $this->event_manager,
            $this->system_event_manager,
            $this->project_history_dao
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
    }

    public function testUpdateProjectStatus(): void
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('get')->with('new_name')->andReturn(null);
        $request->shouldReceive('get')->with('group_id')->andReturn(111);

        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $request->shouldReceive('getValidated')->withArgs(['form_status', 'string', 'H'])->andReturn('A');
        $request->shouldReceive('getValidated')->withArgs(['group_type', 'string', 'type'])->andReturn('type');

        $this->project->shouldReceive('getGroupId')->andReturn(111);
        $this->project->shouldReceive('getStatus')->andReturn('H');
        $this->project->shouldReceive('getType')->andReturn('type');


        $this->dao->shouldReceive("updateProjectStatusAndType");

        $GLOBALS['Response']->shouldReceive("addFeedback")->withArgs([Feedback::INFO, 'Updating Project Info']);

        $this->project_history_dao->shouldReceive('groupAddHistory');

        $this->event_manager->shouldReceive('processEvent');

        $this->project_manager->shouldReceive('removeProjectFromCache');

        $this->project_edit_controller->updateProject($request);
    }

    public function testUpdateProjectStatusThrowErrorIfTryingToPassAProjectToPending(): void
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('get')->with('new_name')->andReturn(null);
        $request->shouldReceive('get')->with('group_id')->andReturn(111);

        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $request->shouldReceive('getValidated')->withArgs(['form_status', 'string', 'D'])->andReturn('A');
        $request->shouldReceive('getValidated')->withArgs(['group_type', 'string', 'type'])->andReturn('type');

        $this->project->shouldReceive('getGroupId')->andReturn(111);
        $this->project->shouldReceive('getStatus')->andReturn('D');
        $this->project->shouldReceive('getType')->andReturn('type');

        $GLOBALS['Response']->shouldReceive("addFeedback")->withArgs([Feedback::ERROR, 'A deleted project can not be restored.']);
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        $this->dao->shouldReceive("updateProjectStatusAndType")->never();

        $this->project_edit_controller->updateProject($request);
    }

    public function testUpdateProjectStatusThrowErrorIfProjectAlreadyDeleted2(): void
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('get')->with('new_name')->andReturn(null);
        $request->shouldReceive('get')->with('group_id')->andReturn(111);

        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $request->shouldReceive('getValidated')->withArgs(['form_status', 'string', 'D'])->andReturn('P');
        $request->shouldReceive('getValidated')->withArgs(['group_type', 'string', 'type'])->andReturn('type');

        $this->project->shouldReceive('getGroupId')->andReturn(111);
        $this->project->shouldReceive('getStatus')->andReturn('D');
        $this->project->shouldReceive('getType')->andReturn('type');

        $GLOBALS['Response']->shouldReceive("addFeedback")->withArgs([Feedback::ERROR, 'Switching the project status back to "pending" is not possible.'])->once();
        $GLOBALS['Response']->shouldReceive('redirect')->once();

        $this->dao->shouldReceive("updateProjectStatusAndType")->never();

        $this->project_edit_controller->updateProject($request);
    }
}
