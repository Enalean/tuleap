<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Luracast\Restler\RestException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use ProjectManager;
use Psr\Log\NullLogger;
use Tracker;
use TrackerFactory;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Roadmap\RetrieveReportToFilterArtifacts;
use Tuleap\Roadmap\RoadmapWidgetDao;
use Tuleap\Roadmap\Stub\RetrieveReportToFilterArtifactsStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Progress\MethodBasedOnEffort;
use Tuleap\Tracker\Semantic\Progress\MethodNotConfigured;
use Tuleap\Tracker\Semantic\Progress\SemanticProgress;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDao;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\TrackerColor;

class RoadmapTasksRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private const ROADMAP_ID         = 42;
    private const PROJECT_ID         = 101;
    private const TRACKER_ID         = 111;
    private const ANOTHER_TRACKER_ID = 112;

    /**
     * @var RoadmapTasksRetriever
     */
    private $retriever;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RoadmapWidgetDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\URLVerification
     */
    private $url_verification;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RoadmapTasksOutOfDateFilter
     */
    private $tasks_filter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticProgressBuilder
     */
    private $progress_builder;

    protected function setUp(): void
    {
        $this->dao                        = Mockery::mock(RoadmapWidgetDao::class);
        $this->project_manager            = Mockery::mock(ProjectManager::class);
        $this->user_manager               = Mockery::mock(\UserManager::class);
        $this->url_verification           = Mockery::mock(\URLVerification::class);
        $this->tracker_factory            = Mockery::mock(TrackerFactory::class);
        $this->semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);
        $this->artifact_factory           = Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->tasks_filter               = Mockery::mock(RoadmapTasksOutOfDateFilter::class);
        $this->progress_builder           = Mockery::mock(SemanticProgressBuilder::class);

        $this->user = UserTestBuilder::anActiveUser()->build();
    }

    private function getRetriever(
        IRetrieveDependencies $dependencies_retriever,
        RetrieveReportToFilterArtifacts $report_to_filter_retriever,
    ): RoadmapTasksRetriever {
        return new RoadmapTasksRetriever(
            $this->dao,
            $this->project_manager,
            $this->user_manager,
            $this->url_verification,
            $this->tracker_factory,
            $this->semantic_timeframe_builder,
            $this->artifact_factory,
            $dependencies_retriever,
            $this->tasks_filter,
            $this->progress_builder,
            new NullLogger(),
            $report_to_filter_retriever,
        );
    }

    private function getRetrieverWithoutDependencies(): RoadmapTasksRetriever
    {
        return $this->getRetriever(new class implements IRetrieveDependencies {
            public function getDependencies(Artifact $artifact): array
            {
                return [];
            }
        }, RetrieveReportToFilterArtifactsStub::withoutReport());
    }

    public function test404IfRoadmapNotFound(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn([]);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfProjectNotFound(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->once()
            ->andThrow(Project_AccessProjectNotFoundException::class);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test403IfUserCannotAccessProject(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->once()
            ->andThrow(Mockery::spy(Project_AccessException::class));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfTrackerNotFound(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification->shouldReceive('userCanAccessProject')->once();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturnNull();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfTrackerIsNotActive(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification->shouldReceive('userCanAccessProject')->once();

        $tracker = Mockery::mock(
            Tracker::class,
            ['isActive' => false, 'userCanView' => true]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfTrackerIsNotAccessibleForUser(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $tracker = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => false]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfTrackerDoesNotHaveTitleField(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $tracker = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => null]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfTitleFieldIsNotReadable(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => false]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfTimeframeIsNotDefined(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->once()
            ->andReturn(new SemanticTimeframe($tracker, new TimeframeNotConfigured()));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfStartDateIsNotReadable(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->once()
            ->andReturn(
                new SemanticTimeframe(
                    $tracker,
                    new TimeframeWithEndDate(
                        Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => false]),
                        Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]),
                    )
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfEndDateIsNotReadable(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->once()
            ->andReturn(
                new SemanticTimeframe(
                    $tracker,
                    new TimeframeWithEndDate(
                        Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]),
                        Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => false]),
                    )
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfDurationIsNotReadable(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->once()
            ->andReturn(
                new SemanticTimeframe(
                    $tracker,
                    new TimeframeWithDuration(
                        Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]),
                        Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['userCanRead' => false])
                    )
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies()->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function testItReturnsAPaginatedListOfReadableTaskRepresentation(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            [
                'isActive'      => true,
                'userCanView'   => true,
                'getTitleField' => $title_field,
                'getId'         => self::TRACKER_ID,
                'getColor'      => TrackerColor::fromName('acid-green'),
                'getProject'    => $project,
            ]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $start_date_field       = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);
        $end_date_field         = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);
        $total_effort_field     = Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['userCanRead' => true]);
        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['userCanRead' => true]);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn(new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date_field, $end_date_field)));

        $this->progress_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn(
                new SemanticProgress(
                    $tracker,
                    new MethodBasedOnEffort(
                        Mockery::mock(SemanticProgressDao::class),
                        $total_effort_field,
                        $remaining_effort_field
                    )
                )
            );

        $task_201 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => true,
                'getId'       => 201,
                'getXRef'     => 'task #201',
                'getUri'      => '/plugins/tracker?aid=201',
                'getTitle'    => 'Do this',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );
        $task_202 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => false,
                'getId'       => 202,
                'getXRef'     => 'task #202',
                'getUri'      => '/plugins/tracker?aid=202',
                'getTitle'    => 'Do that',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );
        $task_203 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => true,
                'getId'       => 203,
                'getXRef'     => 'task #203',
                'getUri'      => '/plugins/tracker?aid=203',
                'getTitle'    => 'Do those',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );
        $task_204 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => true,
                'getId'       => 204,
                'getXRef'     => 'task #204',
                'getUri'      => '/plugins/tracker?aid=204',
                'getTitle'    => 'Done more than 1 year ago',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );

        $this->mockDate($task_201, $start_date_field, 1234567890);
        $this->mockDate($task_201, $end_date_field, 1234567890);
        $this->mockDate($task_203, $start_date_field, null);
        $this->mockDate($task_203, $end_date_field, 1234567890);

        $this->mockEffort($task_201, $total_effort_field, 8);
        $this->mockEffort($task_201, $remaining_effort_field, 5);
        $this->mockEffort($task_203, $total_effort_field, 3);
        $this->mockEffort($task_203, $remaining_effort_field, 0.75);

        $artifacts = [$task_201, $task_202, $task_203, $task_204];
        $this->artifact_factory
            ->shouldReceive('getPaginatedArtifactsByListOfTrackerIds')
            ->with([self::TRACKER_ID], 0, 10)
            ->once()
            ->andReturn(
                new \Tracker_Artifact_PaginatedArtifacts($artifacts, 4)
            );

        $this->tasks_filter->shouldReceive('filterOutOfDateArtifacts')
            ->with(
                $artifacts,
                Mockery::type(\DateTimeImmutable::class),
                $this->user,
                Mockery::type(TrackersWithUnreadableStatusCollection::class)
            )
            ->once()
            ->andReturn([$task_201, $task_202, $task_203]);

        $dependency_retriever = new class implements IRetrieveDependencies {
            public function getDependencies(Artifact $artifact): array
            {
                if ($artifact->getId() === 201) {
                    return [new DependenciesByNature('depends_on', [202, 203])];
                }

                return [];
            }
        };

        $collection = $this
            ->getRetriever($dependency_retriever, RetrieveReportToFilterArtifactsStub::withoutReport())
            ->getTasks(self::ROADMAP_ID, 0, 10);

        self::assertEquals(4, $collection->getTotalSize());
        self::assertCount(2, $collection->getRepresentations());
        self::assertEquals(
            [
                new TaskRepresentation(
                    201,
                    'task #201',
                    '/plugins/tracker?aid=201',
                    'Do this',
                    'acid-green',
                    0.375,
                    "",
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    false,
                    true,
                    '',
                    [new DependenciesByNature('depends_on', [202, 203])],
                    new ProjectReference($project),
                ),
                new TaskRepresentation(
                    203,
                    'task #203',
                    '/plugins/tracker?aid=203',
                    'Do those',
                    'acid-green',
                    0.75,
                    "",
                    null,
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    false,
                    true,
                    '',
                    [],
                    new ProjectReference($project),
                ),
            ],
            $collection->getRepresentations()
        );
    }

    public function testItReturnsAPaginatedListOfReadableTaskRepresentationFromReport(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            [
                'isActive'      => true,
                'userCanView'   => true,
                'getTitleField' => $title_field,
                'getId'         => self::TRACKER_ID,
                'getColor'      => TrackerColor::fromName('acid-green'),
                'getProject'    => $project,
            ]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $start_date_field       = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);
        $end_date_field         = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);
        $total_effort_field     = Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['userCanRead' => true]);
        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['userCanRead' => true]);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn(new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date_field, $end_date_field)));

        $this->progress_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn(
                new SemanticProgress(
                    $tracker,
                    new MethodBasedOnEffort(
                        Mockery::mock(SemanticProgressDao::class),
                        $total_effort_field,
                        $remaining_effort_field
                    )
                )
            );

        $task_201 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => true,
                'getId'       => 201,
                'getXRef'     => 'task #201',
                'getUri'      => '/plugins/tracker?aid=201',
                'getTitle'    => 'Do this',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );
        $task_202 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => false,
                'getId'       => 202,
                'getXRef'     => 'task #202',
                'getUri'      => '/plugins/tracker?aid=202',
                'getTitle'    => 'Do that',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );
        $task_203 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => true,
                'getId'       => 203,
                'getXRef'     => 'task #203',
                'getUri'      => '/plugins/tracker?aid=203',
                'getTitle'    => 'Do those',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );
        $task_204 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => true,
                'getId'       => 204,
                'getXRef'     => 'task #204',
                'getUri'      => '/plugins/tracker?aid=204',
                'getTitle'    => 'Done more than 1 year ago',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );

        $this->mockDate($task_201, $start_date_field, 1234567890);
        $this->mockDate($task_201, $end_date_field, 1234567890);
        $this->mockDate($task_203, $start_date_field, null);
        $this->mockDate($task_203, $end_date_field, 1234567890);

        $this->mockEffort($task_201, $total_effort_field, 8);
        $this->mockEffort($task_201, $remaining_effort_field, 5);
        $this->mockEffort($task_203, $total_effort_field, 3);
        $this->mockEffort($task_203, $remaining_effort_field, 0.75);

        $artifacts = [$task_201, $task_202, $task_203, $task_204];
        $this->artifact_factory
            ->shouldReceive('getPaginatedArtifactsByListOfArtifactIds')
            ->with([201, 202], 0, 10)
            ->once()
            ->andReturn(
                new \Tracker_Artifact_PaginatedArtifacts($artifacts, 4)
            );

        $this->tasks_filter->shouldReceive('filterOutOfDateArtifacts')
            ->with(
                $artifacts,
                Mockery::type(\DateTimeImmutable::class),
                $this->user,
                Mockery::type(TrackersWithUnreadableStatusCollection::class)
            )
            ->once()
            ->andReturn([$task_201, $task_202, $task_203]);

        $dependency_retriever = new class implements IRetrieveDependencies {
            public function getDependencies(Artifact $artifact): array
            {
                if ($artifact->getId() === 201) {
                    return [new DependenciesByNature('depends_on', [202, 203])];
                }

                return [];
            }
        };

        $report = $this->createMock(\Tracker_Report::class);
        $report->method('getMatchingIds')->willReturn(['id' => '201,202']);

        $collection = $this
            ->getRetriever($dependency_retriever, RetrieveReportToFilterArtifactsStub::withReport($report))
            ->getTasks(self::ROADMAP_ID, 0, 10);

        self::assertEquals(4, $collection->getTotalSize());
        self::assertCount(2, $collection->getRepresentations());
        self::assertEquals(
            [
                new TaskRepresentation(
                    201,
                    'task #201',
                    '/plugins/tracker?aid=201',
                    'Do this',
                    'acid-green',
                    0.375,
                    "",
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    false,
                    true,
                    '',
                    [new DependenciesByNature('depends_on', [202, 203])],
                    new ProjectReference($project),
                ),
                new TaskRepresentation(
                    203,
                    'task #203',
                    '/plugins/tracker?aid=203',
                    'Do those',
                    'acid-green',
                    0.75,
                    "",
                    null,
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    false,
                    true,
                    '',
                    [],
                    new ProjectReference($project),
                ),
            ],
            $collection->getRepresentations()
        );
    }

    public function testItExcludesArtifactsThatAreChildOfAnArtifactOfTheSameTracker(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([self::TRACKER_ID]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            [
                'isActive'      => true,
                'userCanView'   => true,
                'getTitleField' => $title_field,
                'getId'         => self::TRACKER_ID,
                'getColor'      => TrackerColor::fromName('acid-green'),
                'getProject'    => $project,
            ]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $start_date_field       = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);
        $end_date_field         = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);
        $total_effort_field     = Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['userCanRead' => true]);
        $remaining_effort_field = Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['userCanRead' => true]);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn(new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date_field, $end_date_field)));

        $this->progress_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn(
                new SemanticProgress(
                    $tracker,
                    new MethodBasedOnEffort(
                        Mockery::mock(SemanticProgressDao::class),
                        $total_effort_field,
                        $remaining_effort_field
                    )
                )
            );

        $task_201 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => true,
                'getId'       => 201,
                'getXRef'     => 'task #201',
                'getUri'      => '/plugins/tracker?aid=201',
                'getTitle'    => 'Do this',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );
        $task_202 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => false,
                'getId'       => 202,
                'getXRef'     => 'task #202',
                'getUri'      => '/plugins/tracker?aid=202',
                'getTitle'    => 'Do that',
                'getTracker'  => $tracker,
                'getParent'   => $task_201,
                'isOpen'      => true,
            ]
        );

        $this->mockDate($task_201, $start_date_field, 1234567890);
        $this->mockDate($task_201, $end_date_field, 1234567890);
        $this->mockDate($task_202, $start_date_field, null);
        $this->mockDate($task_202, $end_date_field, 1234567890);

        $this->mockEffort($task_201, $total_effort_field, 8);
        $this->mockEffort($task_201, $remaining_effort_field, 5);
        $this->mockEffort($task_202, $total_effort_field, 3);
        $this->mockEffort($task_202, $remaining_effort_field, 0.75);

        $artifacts = [$task_201, $task_202];
        $this->artifact_factory
            ->shouldReceive('getPaginatedArtifactsByListOfTrackerIds')
            ->with([self::TRACKER_ID], 0, 10)
            ->once()
            ->andReturn(
                new \Tracker_Artifact_PaginatedArtifacts($artifacts, 2)
            );

        $this->tasks_filter->shouldReceive('filterOutOfDateArtifacts')
            ->with(
                $artifacts,
                Mockery::type(\DateTimeImmutable::class),
                $this->user,
                Mockery::type(TrackersWithUnreadableStatusCollection::class)
            )
            ->once()
            ->andReturn([$task_201, $task_202]);

        $dependency_retriever = new class implements IRetrieveDependencies {
            public function getDependencies(Artifact $artifact): array
            {
                return [];
            }
        };

        $collection = $this
            ->getRetriever($dependency_retriever, RetrieveReportToFilterArtifactsStub::withoutReport())
            ->getTasks(self::ROADMAP_ID, 0, 10);

        self::assertEquals(2, $collection->getTotalSize());
        self::assertCount(1, $collection->getRepresentations());
        self::assertEquals(
            [
                new TaskRepresentation(
                    201,
                    'task #201',
                    '/plugins/tracker?aid=201',
                    'Do this',
                    'acid-green',
                    0.375,
                    "",
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    false,
                    true,
                    '',
                    [],
                    new ProjectReference($project),
                ),
            ],
            $collection->getRepresentations()
        );
    }

    public function testItReturnsAPaginatedListOfReadableTaskRepresentationBelongingToDifferentTrackers(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                ]
            );
        $this->dao
            ->shouldReceive('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->andReturn([
                self::TRACKER_ID,
                self::ANOTHER_TRACKER_ID,
            ]);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field         = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $another_title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);

        $tracker = Mockery::mock(
            Tracker::class,
            [
                'isActive'      => true,
                'userCanView'   => true,
                'getTitleField' => $title_field,
                'getId'         => self::TRACKER_ID,
                'getColor'      => TrackerColor::fromName('acid-green'),
                'getProject'    => $project,
            ]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $another_tracker = Mockery::mock(
            Tracker::class,
            [
                'isActive'      => true,
                'userCanView'   => true,
                'getTitleField' => $another_title_field,
                'getId'         => self::ANOTHER_TRACKER_ID,
                'getColor'      => TrackerColor::fromName('red-wine'),
                'getProject'    => $project,
            ]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::ANOTHER_TRACKER_ID)
            ->andReturn($another_tracker);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);
        $end_date_field   = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);

        $another_start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);
        $another_end_date_field   = Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn(new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date_field, $end_date_field)));
        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($another_tracker)
            ->andReturn(
                new SemanticTimeframe(
                    $another_tracker,
                    new TimeframeWithEndDate($another_start_date_field, $another_end_date_field)
                )
            );

        $this->progress_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->andReturn(
                new SemanticProgress($tracker, new MethodNotConfigured())
            );
        $this->progress_builder
            ->shouldReceive('getSemantic')
            ->with($another_tracker)
            ->andReturn(
                new SemanticProgress($another_tracker, new MethodNotConfigured())
            );

        $task_201 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => true,
                'getId'       => 201,
                'getXRef'     => 'task #201',
                'getUri'      => '/plugins/tracker?aid=201',
                'getTitle'    => 'Do this',
                'getTracker'  => $tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );
        $task_203 = Mockery::mock(
            Artifact::class,
            [
                'userCanView' => true,
                'getId'       => 203,
                'getXRef'     => 'task #203',
                'getUri'      => '/plugins/tracker?aid=203',
                'getTitle'    => 'Do those',
                'getTracker'  => $another_tracker,
                'getParent'   => null,
                'isOpen'      => true,
            ]
        );

        $this->mockDate($task_201, $start_date_field, 1234567890);
        $this->mockDate($task_201, $end_date_field, 1234567890);
        $this->mockDate($task_203, $another_start_date_field, null);
        $this->mockDate($task_203, $another_end_date_field, 1234567890);

        $artifacts = [$task_201, $task_203];
        $this->artifact_factory
            ->shouldReceive('getPaginatedArtifactsByListOfTrackerIds')
            ->with([self::TRACKER_ID, self::ANOTHER_TRACKER_ID], 0, 10)
            ->once()
            ->andReturn(
                new \Tracker_Artifact_PaginatedArtifacts($artifacts, 2)
            );

        $this->tasks_filter->shouldReceive('filterOutOfDateArtifacts')
            ->with(
                $artifacts,
                Mockery::type(\DateTimeImmutable::class),
                $this->user,
                Mockery::type(TrackersWithUnreadableStatusCollection::class)
            )
            ->once()
            ->andReturn([$task_201, $task_203]);

        $dependency_retriever = new class implements IRetrieveDependencies {
            public function getDependencies(Artifact $artifact): array
            {
                return [];
            }
        };

        $collection = $this
            ->getRetriever($dependency_retriever, RetrieveReportToFilterArtifactsStub::withoutReport())
            ->getTasks(self::ROADMAP_ID, 0, 10);

        self::assertEquals(2, $collection->getTotalSize());
        self::assertCount(2, $collection->getRepresentations());
        self::assertEquals(
            [
                new TaskRepresentation(
                    201,
                    'task #201',
                    '/plugins/tracker?aid=201',
                    'Do this',
                    'acid-green',
                    null,
                    "",
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    false,
                    true,
                    '',
                    [],
                    new ProjectReference($project),
                ),
                new TaskRepresentation(
                    203,
                    'task #203',
                    '/plugins/tracker?aid=203',
                    'Do those',
                    'red-wine',
                    null,
                    "",
                    null,
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    false,
                    true,
                    '',
                    [],
                    new ProjectReference($project),
                ),
            ],
            $collection->getRepresentations()
        );
    }

    private function mockDate(Artifact $artifact, Mockery\MockInterface $date_field, ?int $timestamp): void
    {
        if (! $timestamp) {
            $date_field
                ->shouldReceive('getLastChangesetValue')
                ->with($artifact)
                ->andReturn(null);

            return;
        }

        $value = new \Tracker_Artifact_ChangesetValue_Date(
            1,
            Mockery::mock(\Tracker_Artifact_Changeset::class),
            $date_field,
            false,
            $timestamp
        );

        $date_field
            ->shouldReceive('getLastChangesetValue')
            ->with($artifact)
            ->andReturn($value);
    }

    private function mockEffort(Artifact $artifact, Mockery\MockInterface $effort_field, float $effort)
    {
        $effort_field->shouldReceive('getLastChangesetValue')
            ->with($artifact)
            ->andReturn(
                new \Tracker_Artifact_ChangesetValue_Float(
                    1,
                    Mockery::mock(\Tracker_Artifact_Changeset::class),
                    $effort_field,
                    false,
                    $effort
                )
            );
    }
}
