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
use PHPUnit\Framework\MockObject\MockObject;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use ProjectManager;
use Psr\Log\NullLogger;
use Tracker;
use TrackerFactory;
use Tuleap\Roadmap\RoadmapWidgetDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeNotConfigured;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithDuration;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;

final class IterationsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ROADMAP_ID           = 42;
    private const PROJECT_ID           = 101;
    private const ITERATION_TRACKER_ID = 111;

    private IterationsRetriever $retriever;
    private \PHPUnit\Framework\MockObject\MockObject&RoadmapWidgetDao $dao;
    private \PHPUnit\Framework\MockObject\MockObject $project_manager;
    private \PHPUnit\Framework\MockObject\MockObject&\UserManager $user_manager;
    private \PHPUnit\Framework\MockObject\MockObject&\URLVerification $url_verification;
    private \PHPUnit\Framework\MockObject\MockObject&TrackerFactory $tracker_factory;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticTimeframeBuilder $semantic_timeframe_builder;
    private \PHPUnit\Framework\MockObject\MockObject&\Tracker_ArtifactFactory $artifact_factory;
    private \PFUser $user;
    private \PHPUnit\Framework\MockObject\MockObject&Tracker $tracker;

    protected function setUp(): void
    {
        $this->dao                        = $this->createMock(RoadmapWidgetDao::class);
        $this->project_manager            = $this->createMock(ProjectManager::class);
        $this->user_manager               = $this->createMock(\UserManager::class);
        $this->url_verification           = $this->createMock(\URLVerification::class);
        $this->tracker_factory            = $this->createMock(TrackerFactory::class);
        $this->semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $this->artifact_factory           = $this->createMock(\Tracker_ArtifactFactory::class);

        $this->user    = UserTestBuilder::anActiveUser()->build();
        $this->tracker = $this->createMock(Tracker::class);

        $this->retriever = new IterationsRetriever(
            $this->dao,
            $this->project_manager,
            $this->user_manager,
            $this->url_verification,
            $this->tracker_factory,
            $this->semantic_timeframe_builder,
            $this->artifact_factory,
            new NullLogger()
        );
    }

    public function test404IfRoadmapNotFound(): void
    {
        $this->mockRoadmapConfig([]);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->retriever->getIterations(self::ROADMAP_ID, 1, 0, 10);
    }

    public function test404IfProjectNotFound(): void
    {
        $this->mockRoadmapConfig(
            [
                'id'         => self::ROADMAP_ID,
                'owner_id'   => self::PROJECT_ID,
                'owner_type' => 'g',
                'title'      => 'My Roadmap',
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->willThrowException(new Project_AccessProjectNotFoundException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->retriever->getIterations(self::ROADMAP_ID, 1, 0, 10);
    }

    public function test403IfUserCannotAccessProject(): void
    {
        $this->mockRoadmapConfig(
            [
                'id'         => self::ROADMAP_ID,
                'owner_id'   => self::PROJECT_ID,
                'owner_type' => 'g',
                'title'      => 'My Roadmap',
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->willThrowException(
                new class extends Project_AccessException {
                }
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->retriever->getIterations(self::ROADMAP_ID, 1, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfNoIterationTrackerConfigured(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => null,
                'lvl2_iteration_tracker_id' => null,
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfIterationTrackerCannotBeRetrieved(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfIterationTrackerIsDeleted(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfIterationTrackerCannotBeReadByUser(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfIterationTrackerDoesNotHaveTitleField(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);
        $this->tracker
            ->method('getTitleField')
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfTitleFieldIsNotReadable(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(false);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfTimeframeIsNotDefined(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(new SemanticTimeframe($this->tracker, new TimeframeNotConfigured()));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfStartDateIsNotReadable(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(false);

        $end_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $this->tracker,
                    new TimeframeWithEndDate(
                        $start_date_field,
                        $end_date_field,
                    )
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfEndDateIsNotReadable(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $end_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->method('userCanRead')->willReturn(false);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $this->tracker,
                    new TimeframeWithEndDate(
                        $start_date_field,
                        $end_date_field,
                    )
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function test400IfDurationIsNotReadable(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $duration_field = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $duration_field->method('userCanRead')->willReturn(false);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $this->tracker,
                    new TimeframeWithDuration(
                        $start_date_field,
                        $duration_field,
                    )
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function testItSkipsArtifactsThatAreNotReadable(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $duration_field = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $duration_field->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $this->tracker,
                    new TimeframeWithDuration(
                        $start_date_field,
                        $duration_field,
                    )
                )
            );

        $iteration = $this->createMock(Artifact::class);
        $iteration->method('userCanView')->willReturn(false);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByTrackerId')
            ->with(self::ITERATION_TRACKER_ID, 0, 10, false)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts([$iteration], 1)
            );

        $collection = $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
        self::assertEquals(1, $collection->getTotalSize());
        self::assertEquals([], $collection->getRepresentations());
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function testItSkipsArtifactsThatDoNotHaveStartDate(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $end_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $this->tracker,
                    new TimeframeWithEndDate(
                        $start_date_field,
                        $end_date_field,
                    )
                )
            );

        $iteration = $this->createMock(Artifact::class);
        $iteration->method('userCanView')->willReturn(true);
        $iteration->method('getTracker')->willReturn($this->tracker);
        $this->mockDate($iteration, $start_date_field, null);
        $this->mockDate($iteration, $end_date_field, null);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByTrackerId')
            ->with(self::ITERATION_TRACKER_ID, 0, 10, false)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts([$iteration], 1)
            );

        $collection = $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
        self::assertEquals(1, $collection->getTotalSize());
        self::assertEquals([], $collection->getRepresentations());
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function testItSkipsArtifactsThatDoNotHaveEndDate(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $end_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $this->tracker,
                    new TimeframeWithEndDate(
                        $start_date_field,
                        $end_date_field,
                    )
                )
            );

        $iteration = $this->createMock(Artifact::class);
        $iteration->method('userCanView')->willReturn(true);
        $iteration->method('getTracker')->willReturn($this->tracker);
        $this->mockDate($iteration, $start_date_field, 1234567890);
        $this->mockDate($iteration, $end_date_field, null);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByTrackerId')
            ->with(self::ITERATION_TRACKER_ID, 0, 10, false)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts([$iteration], 1)
            );

        $collection = $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
        self::assertEquals(1, $collection->getTotalSize());
        self::assertEquals([], $collection->getRepresentations());
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function testItSkipsArtifactsThatDoNotHaveATitle(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $end_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $this->tracker,
                    new TimeframeWithEndDate(
                        $start_date_field,
                        $end_date_field,
                    )
                )
            );

        $iteration = $this->createMock(Artifact::class);
        $iteration->method('userCanView')->willReturn(true);
        $iteration->method('getTracker')->willReturn($this->tracker);
        $iteration->method('getTitle')->willReturn("");
        $this->mockDate($iteration, $start_date_field, 1234567890);
        $this->mockDate($iteration, $end_date_field, 1234567890);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByTrackerId')
            ->with(self::ITERATION_TRACKER_ID, 0, 10, false)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts([$iteration], 1)
            );

        $collection = $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
        self::assertEquals(1, $collection->getTotalSize());
        self::assertEquals([], $collection->getRepresentations());
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function testItSkipsArtifactsThatHaveEndDateLesserThanStartDate(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $end_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $this->tracker,
                    new TimeframeWithEndDate(
                        $start_date_field,
                        $end_date_field,
                    )
                )
            );

        $iteration = $this->createMock(Artifact::class);
        $iteration->method('userCanView')->willReturn(true);
        $iteration->method('getTracker')->willReturn($this->tracker);
        $iteration->method('getTitle')->willReturn("");
        $this->mockDate($iteration, $start_date_field, 1234567890);
        $this->mockDate($iteration, $end_date_field, 1123456789);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByTrackerId')
            ->with(self::ITERATION_TRACKER_ID, 0, 10, false)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts([$iteration], 1)
            );

        $collection = $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
        self::assertEquals(1, $collection->getTotalSize());
        self::assertEquals([], $collection->getRepresentations());
    }

    /**
     * @testWith [1]
     *           [2]
     */
    public function testItReturnsPaginatedListOfIterationRepresentation(int $level): void
    {
        $this->mockRoadmapConfig(
            [
                'id'                        => self::ROADMAP_ID,
                'owner_id'                  => self::PROJECT_ID,
                'owner_type'                => 'g',
                'title'                     => 'My Roadmap',
                'lvl1_iteration_tracker_id' => ($level === 1 ? self::ITERATION_TRACKER_ID : null),
                'lvl2_iteration_tracker_id' => ($level === 2 ? self::ITERATION_TRACKER_ID : null),
            ]
        );

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(self::PROJECT_ID)
            ->willReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->tracker_factory
            ->expects(self::once())
            ->method('getTrackerById')
            ->with(self::ITERATION_TRACKER_ID)
            ->willReturn($this->tracker);

        $this->tracker
            ->method('isActive')
            ->willReturn(true);
        $this->tracker
            ->method('userCanView')
            ->with($this->user)
            ->willReturn(true);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $this->tracker
            ->method('getTitleField')
            ->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $end_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder
            ->method('getSemantic')
            ->with($this->tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $this->tracker,
                    new TimeframeWithEndDate(
                        $start_date_field,
                        $end_date_field,
                    )
                )
            );

        $iteration = $this->createMock(Artifact::class);
        $iteration->method('userCanView')->willReturn(true);
        $iteration->method('getTracker')->willReturn($this->tracker);
        $iteration->method('getTitle')->willReturn("Sprint W42");
        $iteration->method('getId')->willReturn(123);
        $iteration->method('getUri')->willReturn("/path/to/123");
        $this->mockDate($iteration, $start_date_field, 1234567890);
        $this->mockDate($iteration, $end_date_field, 1234567890);

        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByTrackerId')
            ->with(self::ITERATION_TRACKER_ID, 0, 10, false)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts([$iteration], 1)
            );

        $collection = $this->retriever->getIterations(self::ROADMAP_ID, $level, 0, 10);
        self::assertEquals(1, $collection->getTotalSize());
        self::assertEquals(
            [
                new IterationRepresentation(
                    123,
                    '/path/to/123',
                    "Sprint W42",
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                    (new \DateTimeImmutable())->setTimestamp(1234567890),
                ),
            ],
            $collection->getRepresentations()
        );
    }

    private function mockDate(Artifact $artifact, MockObject $date_field, ?int $timestamp): void
    {
        if (! $timestamp) {
            $date_field
                ->method('getLastChangesetValue')
                ->with($artifact)
                ->willReturn(null);

            return;
        }

        $value = new \Tracker_Artifact_ChangesetValue_Date(
            1,
            $this->createMock(\Tracker_Artifact_Changeset::class),
            $date_field,
            false,
            $timestamp
        );

        $date_field
            ->method('getLastChangesetValue')
            ->with($artifact)
            ->willReturn($value);
    }

    private function mockRoadmapConfig(array $db_result): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn($db_result);
    }
}
