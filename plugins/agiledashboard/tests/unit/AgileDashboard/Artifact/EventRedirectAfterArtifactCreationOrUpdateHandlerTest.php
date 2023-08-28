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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Planning;
use Planning_ArtifactLinker;
use Planning_MilestoneFactory;
use Planning_MilestonePaneFactory;
use PlanningFactory;
use Project;
use ProjectManager;
use Tracker;
use Tracker_Artifact_Redirect;
use Tuleap\AgileDashboard\Planning\NotFoundException;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Milestone\PaneInfo;

class EventRedirectAfterArtifactCreationOrUpdateHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    private const PROJECT_ID   = 101;
    private const ARTIFACT_ID  = 1001;
    private const PLANNING_ID  = 1;
    private const MILESTONE_ID = 666;

    /**
     * @var AgileDashboard_PaneRedirectionExtractor
     */
    private $params_extractor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_ArtifactLinker
     */
    private $artifact_linker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;
    /**
     * @var RedirectParameterInjector
     */
    private $injector;
    /**
     * @var EventRedirectAfterArtifactCreationOrUpdateHandler
     */
    private $processor;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var Planning
     */
    private $planning;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $milestone_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_MilestonePaneFactory
     */
    private $pane_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|null
     */
    private $project;
    /**
     * @var HomeServiceRedirectionExtractor
     */
    private $home_service_redirection_extractor;

    protected function setUp(): void
    {
        $this->params_extractor                   = new AgileDashboard_PaneRedirectionExtractor();
        $this->home_service_redirection_extractor = new HomeServiceRedirectionExtractor();
        $this->artifact_linker                    = Mockery::mock(Planning_ArtifactLinker::class);
        $this->planning_factory                   = Mockery::mock(PlanningFactory::class);
        $this->milestone_factory                  = Mockery::mock(Planning_MilestoneFactory::class);
        $this->pane_factory                       = Mockery::mock(Planning_MilestonePaneFactory::class);

        $this->injector = new RedirectParameterInjector(
            $this->params_extractor,
            Mockery::mock(\Tracker_ArtifactFactory::class),
            $GLOBALS['Response'],
            Mockery::spy(\TemplateRenderer::class),
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

        $this->user = Mockery::mock(PFUser::class);

        $this->project = Mockery::mock(Project::class)
            ->shouldReceive(['getID' => self::PROJECT_ID])
            ->getMock();

        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(['getProject' => $this->project])
            ->getMock();

        $tracker->shouldReceive('getGroupId')->andReturn(self::PROJECT_ID);

        $this->artifact = Mockery::mock(Artifact::class)
            ->shouldReceive(
                [
                    'getTracker' => $tracker,
                    'getId'      => self::ARTIFACT_ID,
                ]
            )->getMock();

        $this->planning = PlanningBuilder::aPlanning(self::PROJECT_ID)
            ->withId(self::PLANNING_ID)
            ->build();

        ProjectManager::setInstance(
            Mockery::mock(ProjectManager::class)
                ->shouldReceive(['getProject' => $this->project])
                ->getMock()
        );
    }

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();
    }

    public function testItRedirectsToPlanning(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            )->once();

        $this->planning_factory
            ->shouldReceive('getPlanning')
            ->with(self::PLANNING_ID)
            ->once()
            ->andReturn($this->planning);

        $milestone = Mockery::mock(\Planning_Milestone::class)
            ->shouldReceive(['getArtifact' => Mockery::mock(Artifact::class)])
            ->getMock();

        $this->milestone_factory
            ->shouldReceive('getBareMilestone')
            ->with($this->user, $this->project, self::PLANNING_ID, self::MILESTONE_ID)
            ->once()
            ->andReturn($milestone);

        $this->pane_factory
            ->shouldReceive('getListOfPaneInfo')
            ->once()
            ->andReturn(
                [
                    Mockery::mock(PaneInfo::class)
                        ->shouldReceive(
                            [
                                'getIdentifier' => 'details',
                                'getUri'        => '/path/to/the/pane',
                            ]
                        )->getMock(),
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
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            )->once();

        $this->planning_factory
            ->shouldReceive('getPlanning')
            ->with(self::PLANNING_ID)
            ->once()
            ->andReturn($this->planning);

        $milestone = Mockery::mock(\Planning_Milestone::class)
            ->shouldReceive(['getArtifact' => Mockery::mock(Artifact::class)])
            ->getMock();

        $this->milestone_factory
            ->shouldReceive('getBareMilestone')
            ->with($this->user, $this->project, self::PLANNING_ID, self::MILESTONE_ID)
            ->once()
            ->andReturn($milestone);

        $this->pane_factory
            ->shouldReceive('getListOfPaneInfo')
            ->once()
            ->andReturn([
                Mockery::mock(PaneInfo::class)
                    ->shouldReceive(
                        [
                            'getIdentifier' => 'taskboard',
                            'getUri' => '/path/to/the/pane',
                        ]
                    )->getMock(),
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
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            )->once();

        $this->planning_factory
            ->shouldReceive('getPlanning')
            ->with(self::PLANNING_ID)
            ->once()
            ->andReturn($this->planning);

        $milestone = Mockery::mock(\Planning_Milestone::class)
            ->shouldReceive(['getArtifact' => null])
            ->getMock();

        $this->milestone_factory
            ->shouldReceive('getBareMilestone')
            ->with($this->user, $this->project, self::PLANNING_ID, self::MILESTONE_ID)
            ->once()
            ->andReturn($milestone);

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
            ->withUser($this->user)
            ->withParam('planning', ['details' => [(string) self::PLANNING_ID => (string) self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
            ->with(
                $request,
                $this->artifact,
                [
                    'pane'        => 'details',
                    'planning_id' => self::PLANNING_ID,
                    'aid'         => self::MILESTONE_ID,
                    'action'      => 'show',
                ]
            )->once();

        $this->planning_factory
            ->shouldReceive('getPlanning')
            ->with(self::PLANNING_ID)
            ->once()
            ->andReturn($this->planning);

        $milestone = Mockery::mock(\Planning_Milestone::class);
        $this->milestone_factory
            ->shouldReceive('getBareMilestone')
            ->with($this->user, $this->project, self::PLANNING_ID, self::MILESTONE_ID)
            ->once()
            ->andThrow(new NotFoundException(self::PLANNING_ID));

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
            ->shouldReceive('linkBacklogWithPlanningItems')
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
            ->once();

        $this->planning_factory
            ->shouldReceive('getPlanning')
            ->with(self::PLANNING_ID)
            ->once()
            ->andReturnNull();

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
            ->shouldReceive('linkBacklogWithPlanningItems')
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
            ->once();

        $this->planning_factory
            ->shouldReceive('getPlanning')
            ->with(self::PLANNING_ID)
            ->once()
            ->andReturn($this->planning);

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

        $last_milestone_artifact = Mockery::mock(Artifact::class)
            ->shouldReceive(['getId' => 111])
            ->getMock();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
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
            ->once()
            ->andReturn($last_milestone_artifact);

        $this->planning_factory
            ->shouldReceive('getPlanning')
            ->with(self::PLANNING_ID)
            ->once()
            ->andReturn($this->planning);

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
