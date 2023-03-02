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

declare(strict_types=1);

namespace Tuleap\ProjectMilestones\Widget;

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
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBrokenConfigurationException;

final class ProjectMilestonesWidgetRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->project_access_checker = $this->createMock(ProjectAccessChecker::class);

        $this->user = $this->createMock(PFUser::class);

        $this->http = $this->createMock(HTTPRequest::class);
        $this->http->method('getCurrentUser')->willReturn($this->user);

        $this->project_manager        = $this->createMock(ProjectManager::class);
        $this->project_milestones_dao = $this->createMock(ProjectMilestonesDao::class);
        $this->csrf_token             = $this->createMock(CSRFSynchronizerToken::class);
        $this->template_rendered      = $this->createMock(TemplateRenderer::class);
        $this->root_planning          = $this->createMock(Planning::class);
        $this->presenter_builder      = $this->createMock(ProjectMilestonesPresenterBuilder::class);

        $this->project = $this->createMock(Project::class);
        $this->project->method('getUnixName')->willReturn('MyProject');
        $this->project->method('getPublicName')->willReturn('MyProject');
        $this->project->method('getID')->willReturn(101);

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
        $this->project_access_checker->expects(self::once())->method('checkUserCanAccessProject');
        $title = $this->retriever->getTitle($this->project, $this->user);

        $this->assertStringContainsString('MyProject Milestones', $title);
    }

    public function testGetGenericTitleWhenNoProject(): void
    {
        $title = $this->retriever->getTitle(null, $this->user);

        $this->assertStringContainsString('Project Milestones', $title);
    }

    public function testGetGenericTitleWhenUserCanNotAccesProject(): void
    {
        $this->project_access_checker->expects(self::once())->method('checkUserCanAccessProject')->willThrowException(new Project_AccessProjectNotFoundException());
        $title = $this->retriever->getTitle($this->project, $this->user);

        $this->assertStringContainsString('Project Milestones', $title);
    }

    public function testGetRendererContentWhenThereIsProject(): void
    {
        $this->presenter_builder->method('getProjectMilestonePresenter')->willReturn($this->createMock(ProjectMilestonesPresenter::class));
        $this->template_rendered->expects(self::once())->method('renderToString')->willReturn("");
        $this->retriever->getContent($this->project, $this->root_planning);
    }

    public function testGetProjectMilestoneExceptionWhenThereIsNoProject(): void
    {
        $this->presenter_builder->method('getProjectMilestonePresenter')->willThrowException(ProjectMilestonesException::buildProjectDontExist());
        $content = $this->retriever->getContent(null, null);
        $this->assertStringContainsString("Project does not exist.", $content);
    }

    public function testGetExceptionContentWhenThereIsNoProject(): void
    {
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(110);
        $this->presenter_builder->method('getProjectMilestonePresenter')->willThrowException(new TimeframeBrokenConfigurationException($tracker));
        $content = $this->retriever->getContent(null, null);
        $this->assertStringContainsString("Invalid Timeframe Semantic configuration.", $content);
    }

    public function testGetProjectMilestonesPreferencesWhenUserCanSeeProject(): void
    {
        $this->project_access_checker->expects(self::once())->method('checkUserCanAccessProject');
        $this->template_rendered->expects(self::once())->method('renderToString')->with('projectmilestones-preferences', self::isInstanceOf(ProjectMilestonesPreferencesPresenter::class))->willReturn("");

        $this->retriever->getPreferences(10, $this->project, $this->user, $this->csrf_token);
    }

    public function testGetProjectMilestonesPreferencesWithoutProjectWhenUserCanSeeProject(): void
    {
        $this->project_access_checker->expects(self::once())->method('checkUserCanAccessProject')->willThrowException(new Project_AccessProjectNotFoundException());
        $this->template_rendered->expects(self::once())->method('renderToString')->with('projectmilestones-preferences', self::isInstanceOf(ProjectMilestonesPreferencesPresenter::class))->willReturn("");

        $this->retriever->getPreferences(10, $this->project, $this->user, $this->csrf_token);
    }

    public function testCreatingProjectMilestoneWidgetWithAnNonExistingShouldNotCrash(): void
    {
        $this->http->method('getValidated')->willReturn(null);
        $this->project_manager->method('getProjectFromAutocompleter')->willReturn(false);

        $this->project_milestones_dao->expects(self::never())->method('create');

        $this->assertNull($this->retriever->create($this->http));
    }

    public function testCreatingProjectMilestoneLikeXMLImportDoes(): void
    {
        $request = new \Codendi_Request([
            ProjectMilestonesWidgetRetriever::PARAM_SELECTED_PROJECT => ProjectMilestonesWidgetRetriever::VALUE_SELECTED_PROJECT_SELF,
            'project' => ProjectTestBuilder::aProject()->build(),
        ]);

        $this->project_milestones_dao->method('create')->with(101)->willReturn("455");

        $this->assertEquals(455, $this->retriever->create($request));
    }

    public function testUpdatingProjectMilestoneWidgetWithAnNonExistingShouldNotCrash(): void
    {
        $this->http->method('getValidated')->willReturn(null);
        $this->project_manager->method('getProjectFromAutocompleter')->willReturn(false);

        $this->project_milestones_dao->expects(self::never())->method('updateProjectMilestoneId');

        $this->retriever->updatePreferences($this->http);
    }
}
