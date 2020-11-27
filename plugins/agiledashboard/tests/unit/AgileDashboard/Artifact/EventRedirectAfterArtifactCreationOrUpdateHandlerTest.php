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
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_ArtifactLinker;
use PlanningFactory;
use Project;
use Tracker;
use Tracker_Artifact_Redirect;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Tracker\Artifact\Artifact;

class EventRedirectAfterArtifactCreationOrUpdateHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        $this->params_extractor = new AgileDashboard_PaneRedirectionExtractor();
        $this->artifact_linker  = Mockery::mock(Planning_ArtifactLinker::class);
        $this->planning_factory = Mockery::mock(PlanningFactory::class);
        $this->injector         = new RedirectParameterInjector($this->params_extractor);

        $this->processor = new EventRedirectAfterArtifactCreationOrUpdateHandler(
            $this->params_extractor,
            $this->artifact_linker,
            $this->planning_factory,
            $this->injector,
        );

        $project = Mockery::mock(Project::class)
            ->shouldReceive(['getID' => self::PROJECT_ID])
            ->getMock();

        $tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(['getProject' => $project])
            ->getMock();

        $this->artifact = Mockery::mock(Artifact::class)
            ->shouldReceive(
                [
                    'getTracker' => $tracker,
                    'getId'      => self::ARTIFACT_ID,
                ]
            )->getMock();

        $this->planning = new Planning(
            self::PLANNING_ID,
            'name',
            self::PROJECT_ID,
            'backlog_title',
            'plan_title',
            [],
            null
        );
    }

    public function testItRedirectsToPlanning(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('planning', ['details' => [self::PLANNING_ID => self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
            ->with($request, $this->artifact)
            ->once();

        $this->planning_factory
            ->shouldReceive('getPlanning')
            ->with(self::PLANNING_ID)
            ->once()
            ->andReturn($this->planning);

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
                'pane'        => 'details'
            ],
            $redirect->query_parameters
        );
    }

    public function testItRedirectsToPlanningOfNewMilestone(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('planning', ['details' => [self::PLANNING_ID => -1]])
            ->build();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
            ->with($request, $this->artifact)
            ->once();

        $this->planning_factory
            ->shouldReceive('getPlanning')
            ->with(self::PLANNING_ID)
            ->once()
            ->andReturn($this->planning);

        $redirect       = new Tracker_Artifact_Redirect();
        $redirect->mode = Tracker_Artifact_Redirect::STATE_SUBMIT;

        $this->processor->process($request, $redirect, $this->artifact);

        self::assertEquals('/plugins/agiledashboard/', $redirect->base_url);
        self::assertEquals(
            [
                'group_id'    => self::PROJECT_ID,
                'planning_id' => self::PLANNING_ID,
                'action'      => 'show',
                'aid'         => self::ARTIFACT_ID,
                'pane'        => 'details'
            ],
            $redirect->query_parameters
        );
    }

    public function testItRedirectsToTopPlanningIfPlanningCannotBeInstantiated(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('planning', ['details' => [self::PLANNING_ID => self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
            ->with($request, $this->artifact)
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
                'pane'     => 'details'
            ],
            $redirect->query_parameters
        );
    }

    public function testItStaysInTracker(): void
    {
        $request = HTTPRequestBuilder::get()
            ->withParam('planning', ['details' => [self::PLANNING_ID => self::MILESTONE_ID]])
            ->build();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
            ->with($request, $this->artifact)
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
            ->withParam('planning', ['details' => [self::PLANNING_ID => self::MILESTONE_ID]])
            ->build();

        $last_milestone_artifact = Mockery::mock(Artifact::class)
            ->shouldReceive(['getId' => 111])
            ->getMock();

        $this->artifact_linker
            ->shouldReceive('linkBacklogWithPlanningItems')
            ->with($request, $this->artifact)
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
