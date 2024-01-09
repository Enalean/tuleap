<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Artifact;

use AgileDashboard_PaneRedirectionExtractor;
use PFUser;
use Planning;
use Planning_ArtifactLinker;
use Planning_MilestoneFactory;
use Planning_MilestonePaneFactory;
use PlanningFactory;
use Project;
use ProjectManager;
use Tracker_Artifact_Redirect;
use Tuleap\AgileDashboard\Planning\NotFoundException;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Milestone\PaneInfo;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class EventRedirectAfterArtifactCreationOrUpdateHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private const PROJECT_ID   = 101;
    private const ARTIFACT_ID  = 1001;
    private const PLANNING_ID  = 1;
    private const MILESTONE_ID = 666;

    private AgileDashboard_PaneRedirectionExtractor $params_extractor;
    private RedirectParameterInjector $injector;
    private EventRedirectAfterArtifactCreationOrUpdateHandler $processor;
    private Planning $planning;
    private HomeServiceRedirectionExtractor $home_service_redirection_extractor;
    private Project $project;
    private PFUser $user;
    private Planning_ArtifactLinker&\PHPUnit\Framework\MockObject\MockObject $artifact_linker;
    private PlanningFactory&\PHPUnit\Framework\MockObject\MockObject $planning_factory;
    private Planning_MilestoneFactory&\PHPUnit\Framework\MockObject\MockObject $milestone_factory;
    private Planning_MilestonePaneFactory&\PHPUnit\Framework\MockObject\MockObject $pane_factory;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->params_extractor                   = new AgileDashboard_PaneRedirectionExtractor();
        $this->home_service_redirection_extractor = new HomeServiceRedirectionExtractor();
        $this->artifact_linker                    = $this->createMock(Planning_ArtifactLinker::class);
        $this->planning_factory                   = $this->createMock(PlanningFactory::class);
        $this->milestone_factory                  = $this->createMock(Planning_MilestoneFactory::class);
        $this->pane_factory                       = $this->createMock(Planning_MilestonePaneFactory::class);

        $this->injector = new RedirectParameterInjector(
            $this->params_extractor,
            $this->createMock(\Tracker_ArtifactFactory::class),
            $GLOBALS['Response'],
            $this->createMock(\TemplateRenderer::class),
        );

        $this->processor = new EventRedirectAfterArtifactCreationOrUpdateHandler(
            $this->params_extractor,
            $this->home_service_redirection_extractor,
            $this->artifact_linker,
            $this->planning_factory,
            $this->injector,
            $this->milestone_factory,
            $this->pane_factory
        );

        $this->user = UserTestBuilder::anActiveUser()->build();

        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $tracker = TrackerTestBuilder::aTracker()->withProject($this->project)->build();

        $this->artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->inTracker($tracker)->build();

        $this->planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withId(self::PLANNING_ID)
            ->build();
    }

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();
    }

    public function testItRedirectsToPlanning(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->expects(self::once())
            ->method('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            );

        $this->planning_factory
            ->expects(self::once())
            ->method('getPlanning')
            ->with(self::PLANNING_ID)
            ->willReturn($this->planning);

        $milestone = $this->createMock(\Planning_Milestone::class);
        $milestone->method('getArtifact')->willReturn($this->createMock(Artifact::class));

        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestone')
            ->with($this->user, $this->project, self::PLANNING_ID, self::MILESTONE_ID)
            ->willReturn($milestone);

        $this->pane_factory
            ->expects(self::once())
            ->method('getListOfPaneInfo')
            ->willReturn(
                [
                    $this->createConfiguredMock(
                        PaneInfo::class,
                        [
                            'getIdentifier' => 'details',
                            'getUri'        => '/path/to/the/pane',
                        ]
                    ),
                ]
            );

        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;

        $this->processor->process($request, $redirect, $this->artifact);

        self::assertEquals('/path/to/the/pane', $redirect->base_url);
        self::assertEquals([], $redirect->query_parameters);
    }

    public function testItRedirectsToTAgiledashboardHomePage(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('agiledashboard', ['home' => '1'])
            ->build();

        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;

        $this->processor->process($request, $redirect, $this->artifact);

        self::assertEquals('/plugins/agiledashboard/', $redirect->base_url);
        self::assertEquals(
            [
                'group_id' => self::PROJECT_ID,
            ],
            $redirect->query_parameters
        );
    }

    public function testItRedirectsToPlanningWithFallbackToLegacyUrlIfPaneCannotBeFound(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->expects(self::once())
            ->method('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            );

        $this->planning_factory
            ->expects(self::once())
            ->method('getPlanning')
            ->with(self::PLANNING_ID)
            ->willReturn($this->planning);

        $milestone = $this->createMock(\Planning_Milestone::class);
        $milestone->method('getArtifact')->willReturn($this->createMock(Artifact::class));

        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestone')
            ->with($this->user, $this->project, self::PLANNING_ID, self::MILESTONE_ID)
            ->willReturn($milestone);

        $this->pane_factory
            ->expects(self::once())
            ->method('getListOfPaneInfo')
            ->willReturn([
                $this->createConfiguredMock(
                    PaneInfo::class,
                    [
                        'getIdentifier' => 'taskboard',
                        'getUri' => '/path/to/the/pane',
                    ]
                ),
            ]);

        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;

        $this->processor->process($request, $redirect, $this->artifact);
        self::assertEquals('/plugins/agiledashboard/', $redirect->base_url);
        self::assertEquals(
            [
                'group_id'    => self::PROJECT_ID,
                'planning_id' => self::PLANNING_ID,
                'action'      => 'show',
                'aid'         => self::MILESTONE_ID,
                'pane'        => 'details',
            ],
            $redirect->query_parameters
        );
    }

    public function testItRedirectsToPlanningWithFallbackToLegacyUrlIfMilestoneHasNoArtifact(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->expects(self::once())
            ->method('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            );

        $this->planning_factory
            ->expects(self::once())
            ->method('getPlanning')
            ->with(self::PLANNING_ID)
            ->willReturn($this->planning);

        $milestone = $this->createMock(\Planning_Milestone::class);
        $milestone->method('getArtifact')->willReturn(null);

        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestone')
            ->with($this->user, $this->project, self::PLANNING_ID, self::MILESTONE_ID)
            ->willReturn($milestone);

        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;

        $this->processor->process($request, $redirect, $this->artifact);
        self::assertEquals('/plugins/agiledashboard/', $redirect->base_url);
        self::assertEquals(
            [
                'group_id'    => self::PROJECT_ID,
                'planning_id' => self::PLANNING_ID,
                'action'      => 'show',
                'aid'         => self::MILESTONE_ID,
                'pane'        => 'details',
            ],
            $redirect->query_parameters
        );
    }

    public function testItRedirectsToPlanningWithFallbackToLegacyUrlIfMilestoneCannotBeFound(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withProject($this->project)
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->expects(self::once())
            ->method('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            );

        $this->planning_factory
            ->expects(self::once())
            ->method('getPlanning')
            ->with(self::PLANNING_ID)
            ->willReturn($this->planning);

        $this->milestone_factory
            ->expects(self::once())
            ->method('getBareMilestone')
            ->with($this->user, $this->project, self::PLANNING_ID, self::MILESTONE_ID)
            ->willThrowException(new NotFoundException(self::PLANNING_ID));

        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;

        $this->processor->process($request, $redirect, $this->artifact);
        self::assertEquals('/plugins/agiledashboard/', $redirect->base_url);
        self::assertEquals(
            [
                'group_id'    => self::PROJECT_ID,
                'planning_id' => self::PLANNING_ID,
                'action'      => 'show',
                'aid'         => self::MILESTONE_ID,
                'pane'        => 'details',
            ],
            $redirect->query_parameters
        );
    }

    public function testItRedirectsToTopPlanningIfPlanningCannotBeInstantiated(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->expects(self::once())
            ->method('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            );

        $this->planning_factory
            ->expects(self::once())
            ->method('getPlanning')
            ->with(self::PLANNING_ID)
            ->willReturn(null);

        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;

        $this->processor->process($request, $redirect, $this->artifact);

        self::assertEquals('/plugins/agiledashboard/', $redirect->base_url);
        self::assertEquals(
            [
                'group_id' => self::PROJECT_ID,
                'action'   => 'show-top',
                'pane'     => 'details',
            ],
            $redirect->query_parameters
        );
    }

    public function testItStaysInTracker(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->expects(self::once())
            ->method('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            );

        $this->planning_factory
            ->expects(self::once())
            ->method('getPlanning')
            ->with(self::PLANNING_ID)
            ->willReturn($this->planning);

        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_CONTINUE;

        $this->processor->process($request, $redirect, $this->artifact);

        self::assertEquals('', $redirect->base_url);
        self::assertEquals(
            [
                'planning[details][' . self::PLANNING_ID . ']' => self::MILESTONE_ID,
            ],
            $redirect->query_parameters
        );
    }

    public function testItStaysInTrackerAndIncludesTheChildMilestoneIfModeIsSetToCreateParent(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $last_milestone_artifact = ArtifactTestBuilder::anArtifact(111)->build();

        $this->artifact_linker
            ->expects(self::once())
            ->method('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            )
            ->willReturn($last_milestone_artifact);

        $this->planning_factory
            ->expects(self::once())
            ->method('getPlanning')
            ->with(self::PLANNING_ID)
            ->willReturn($this->planning);

        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_CREATE_PARENT;

        $this->processor->process($request, $redirect, $this->artifact);

        self::assertEquals('', $redirect->base_url);
        self::assertEquals(
            [
                'planning[details][' . self::PLANNING_ID . ']' => self::MILESTONE_ID,
                'child_milestone'                              => 111,
            ],
            $redirect->query_parameters
        );
    }
}
