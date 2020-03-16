<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\ProjectMilestones\Widget;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use Tuleap\Project\ProjectAccessChecker;
use HTTPRequest;
use ProjectManager;
use Tuleap\ProjectMilestones\Milestones\ProjectMilestonesDao;
use CSRFSynchronizerToken;
use TemplateRenderer;
use Project;
use PFUser;
use Project_AccessProjectNotFoundException;
use Planning;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;

class ProjectMilestonesWidgetRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var ProjectMilestonesWidgetRetriever
     */
    private $retriever;
    /**
     * @var HTTPRequest|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $http;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectMilestonesDao
     */
    private $project_milestones_dao;
    /**
     * @var CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $csrf_token;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TemplateRenderer
     */
    private $template_rendered;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $root_planning;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectMilestonesPresenterBuilder
     */
    private $presenter_builder;

    public function setUp(): void
    {
        parent::setUp();

        $this->project_access_checker = Mockery::mock(ProjectAccessChecker::class);

        $this->user = Mockery::mock(PFUser::class);

        $this->http = Mockery::mock(HTTPRequest::class);
        $this->http->shouldReceive('getCurrentUser')->andReturn($this->user);

        $this->project_manager        = Mockery::mock(ProjectManager::class);
        $this->project_milestones_dao = Mockery::mock(ProjectMilestonesDao::class);
        $this->csrf_token             = Mockery::mock(CSRFSynchronizerToken::class);
        $this->template_rendered      = Mockery::mock(TemplateRenderer::class);
        $this->root_planning          = Mockery::mock(Planning::class);
        $this->presenter_builder      = Mockery::mock(ProjectMilestonesPresenterBuilder::class);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getUnixName')->andReturn('MyProject');
        $this->project->shouldReceive('getPublicName')->andReturn('MyProject');
        $this->project->shouldReceive('getID')->andReturn(101);

        $this->retriever = new ProjectMilestonesWidgetRetriever(
            $this->project_access_checker,
            $this->project_manager,
            $this->project_milestones_dao,
            $this->template_rendered,
            $this->presenter_builder
        );
    }

    public function testGetGoodTitleWhenThereIsProject(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->once();
        $title = $this->retriever->getTitle($this->project, $this->user);

        $this->assertStringContainsString('MyProject Project Milestones', $title);
    }

    public function testGetGenericTitleWhenNoProject(): void
    {
        $title = $this->retriever->getTitle(null, $this->user);

        $this->assertStringContainsString('Project Milestones', $title);
    }

    public function testGetGenericTitleWhenUserCanNotAccesProject(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->once()->andThrow(Project_AccessProjectNotFoundException::class);
        $title = $this->retriever->getTitle($this->project, $this->user);

        $this->assertStringContainsString('Project Milestones', $title);
    }

    public function testGetRendererContentWhenThereIsProject(): void
    {
        $this->presenter_builder->shouldReceive('getProjectMilestonePresenter')->andReturn(Mockery::mock(ProjectMilestonesPresenter::class));
        $this->template_rendered->shouldReceive('renderToString')->once()->andReturn("");
        $this->retriever->getContent($this->project, $this->root_planning);
    }

    public function testGetProjectMilestoneExceptionWhenThereIsNoProject(): void
    {
        $this->presenter_builder->shouldReceive('getProjectMilestonePresenter')->andThrow(ProjectMilestonesException::buildProjectDontExist());
        $content = $this->retriever->getContent(null, null);
        $this->assertStringContainsString("Project does not exist.", $content);
    }

    public function testGetExceptionContentWhenThereIsNoProject(): void
    {
        $this->presenter_builder->shouldReceive('getProjectMilestonePresenter')->andThrow(Mockery::mock(TimeframeBrokenConfigurationException::class));
        $content = $this->retriever->getContent(null, null);
        $this->assertStringContainsString("Invalid Timeframe Semantic configuration.", $content);
    }

    public function testGetProjectMilestonesPreferencesWhenUserCanSeeProject(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->once();
        $this->template_rendered->shouldReceive('renderToString')->once()->withArgs(['projectmilestones-preferences', ProjectMilestonesPreferencesPresenter::class])->andReturn("");

        $this->retriever->getPreferences(10, $this->project, $this->user, $this->csrf_token);
    }

    public function testGetProjectMilestonesPreferencesWithoutProjectWhenUserCanSeeProject(): void
    {
        $this->project_access_checker->shouldReceive('checkUserCanAccessProject')->once()->andThrow(Project_AccessProjectNotFoundException::class);
        $this->template_rendered->shouldReceive('renderToString')->once()->withArgs(['projectmilestones-preferences', ProjectMilestonesPreferencesPresenter::class])->andReturn("");

        $this->retriever->getPreferences(10, $this->project, $this->user, $this->csrf_token);
    }
}
