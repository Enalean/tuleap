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
use Psr\Log\NullLogger;
use Tracker;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Roadmap\RetrieveReportToFilterArtifacts;
use Tuleap\Roadmap\RoadmapWidgetDao;
use Tuleap\Roadmap\Stub\RetrieveReportToFilterArtifactsStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\RetrieveTracker;
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
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\TrackerColor;

final class RoadmapTasksRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ROADMAP_ID         = 42;
    private const PROJECT_ID         = 101;
    private const TRACKER_ID         = 111;
    private const ANOTHER_TRACKER_ID = 112;

    private \PFUser $user;
    private \Project $project;
    private RoadmapWidgetDao&MockObject $dao;
    private \URLVerification&MockObject $url_verification;
    private SemanticTimeframeBuilder&MockObject $semantic_timeframe_builder;
    private \Tracker_ArtifactFactory&MockObject $artifact_factory;
    private RoadmapTasksOutOfDateFilter&MockObject $tasks_filter;
    private SemanticProgressBuilder&MockObject $progress_builder;

    private function getTracker(int $tracker_id, \Tracker_FormElement_Field_String $title_field, string $color, string $name): Tracker&MockObject
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn($tracker_id);
        $tracker->method('isActive')->willReturn(true);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getTitleField')->willReturn($title_field);
        $tracker->method('getId')->willReturn($tracker_id);
        $tracker->method('getColor')->willReturn(TrackerColor::fromName($color));
        $tracker->method('getProject')->willReturn($this->project);
        $tracker->method('getItemName')->willReturn($name);

        return $tracker;
    }

    protected function setUp(): void
    {
        $this->dao                        = $this->createMock(RoadmapWidgetDao::class);
        $this->url_verification           = $this->createMock(\URLVerification::class);
        $this->semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $this->artifact_factory           = $this->createMock(\Tracker_ArtifactFactory::class);
        $this->tasks_filter               = $this->createMock(RoadmapTasksOutOfDateFilter::class);
        $this->progress_builder           = $this->createMock(SemanticProgressBuilder::class);

        $this->user    = UserTestBuilder::anActiveUser()->build();
        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
    }

    private function getRetriever(
        RetrieveTracker $tracker_factory,
        IRetrieveDependencies $dependencies_retriever,
        RetrieveReportToFilterArtifacts $report_to_filter_retriever,
    ): RoadmapTasksRetriever {
        return new RoadmapTasksRetriever(
            $this->dao,
            ProjectByIDFactoryStub::buildWith($this->project),
            ProvideCurrentUserStub::buildWithUser($this->user),
            $this->url_verification,
            $tracker_factory,
            $this->semantic_timeframe_builder,
            $this->artifact_factory,
            $dependencies_retriever,
            $this->tasks_filter,
            $this->progress_builder,
            new NullLogger(),
            $report_to_filter_retriever,
        );
    }

    private function getRetrieverWithoutDependencies(RetrieveTracker $tracker_factory): RoadmapTasksRetriever
    {
        return $this->getRetriever(
            $tracker_factory,
            new class implements IRetrieveDependencies {
                public function getDependencies(Artifact $artifact): array
                {
                    return [];
                }
            },
            RetrieveReportToFilterArtifactsStub::withoutReport()
        );
    }

    public function test404IfRoadmapNotFound(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn([]);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withoutTracker())->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfProjectNotFound(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->willThrowException($this->createMock(Project_AccessProjectNotFoundException::class));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withoutTracker())->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test403IfUserCannotAccessProject(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->willThrowException($this->createMock(Project_AccessException::class));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withoutTracker())->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfTrackerNotFound(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withoutTracker())->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfTrackerIsNotActive(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject');

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('isActive')->willReturn(false);
        $tracker->method('userCanView')->willReturn(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withTracker($tracker))->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfTrackerIsNotAccessibleForUser(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('isActive')->willReturn(true);
        $tracker->method('userCanView')->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withTracker($tracker))->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfTrackerDoesNotHaveTitleField(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('isActive')->willReturn(true);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getTitleField')->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withTracker($tracker))->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfTitleFieldIsNotReadable(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(false);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('isActive')->willReturn(true);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getTitleField')->willReturn($title_field);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withTracker($tracker))->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfTimeframeIsNotDefined(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('isActive')->willReturn(true);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getTitleField')->willReturn($title_field);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn(new SemanticTimeframe($tracker, new TimeframeNotConfigured()));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withTracker($tracker))->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfStartDateIsNotReadable(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('isActive')->willReturn(true);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getTitleField')->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(false);

        $end_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->method('userCanRead')->willReturn(true);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $tracker,
                    new TimeframeWithEndDate(
                        $start_date_field,
                        $end_date_field,
                    )
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withTracker($tracker))->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfEndDateIsNotReadable(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('isActive')->willReturn(true);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getTitleField')->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $end_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->method('userCanRead')->willReturn(false);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $tracker,
                    new TimeframeWithEndDate(
                        $start_date_field,
                        $end_date_field,
                    )
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withTracker($tracker))->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfDurationIsNotReadable(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(self::TRACKER_ID);
        $tracker->method('isActive')->willReturn(true);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getTitleField')->willReturn($title_field);

        $start_date_field = $this->createMock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->method('userCanRead')->willReturn(true);

        $duration_field = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $duration_field->method('userCanRead')->willReturn(false);

        $this->semantic_timeframe_builder
            ->expects(self::once())
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn(
                new SemanticTimeframe(
                    $tracker,
                    new TimeframeWithDuration(
                        $start_date_field,
                        $duration_field,
                    )
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->getRetrieverWithoutDependencies(RetrieveTrackerStub::withTracker($tracker))->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function testItReturnsAPaginatedListOfReadableTaskRepresentation(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);

        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);
        $tracker = $this->getTracker(self::TRACKER_ID, $title_field, 'acid-green', 'task');

        $start_date_field = DateFieldBuilder::aDateField(1)
            ->withReadPermission($this->user, true)
            ->build();
        $end_date_field   = DateFieldBuilder::aDateField(2)
            ->withReadPermission($this->user, true)
            ->build();

        $total_effort_field = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $total_effort_field->method('userCanRead')->willReturn(true);

        $remaining_effort_field = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $remaining_effort_field->method('userCanRead')->willReturn(true);

        $semantic_timeframe = new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date_field, $end_date_field));
        $this->semantic_timeframe_builder
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn($semantic_timeframe);

        $this->progress_builder
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn(
                new SemanticProgress(
                    $tracker,
                    new MethodBasedOnEffort(
                        $this->createMock(SemanticProgressDao::class),
                        $total_effort_field,
                        $remaining_effort_field
                    )
                )
            );

        $task_201 = $this->anArtifact(201, 'Do this', $tracker, true, $semantic_timeframe);
        $task_202 = $this->anArtifact(202, 'Do that', $tracker, false, $semantic_timeframe);
        $task_203 = $this->anArtifactWithoutStartDate(203, 'Do those', $tracker, $semantic_timeframe);
        $task_204 = $this->anArtifact(204, 'Done more than 1 year ago', $tracker, true, $semantic_timeframe);

        $this->mockEffort($total_effort_field, [201 => 8, 203 => 3]);
        $this->mockEffort($remaining_effort_field, [201 => 5, 203 => 0.75]);

        $artifacts = [$task_201, $task_202, $task_203, $task_204];
        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByListOfTrackerIds')
            ->with([self::TRACKER_ID], 0, 10)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts($artifacts, 4)
            );

        $this->tasks_filter
            ->expects(self::once())
            ->method('filterOutOfDateArtifacts')
            ->willReturn([$task_201, $task_202, $task_203]);

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
            ->getRetriever(RetrieveTrackerStub::withTracker($tracker), $dependency_retriever, RetrieveReportToFilterArtifactsStub::withoutReport())
            ->getTasks(self::ROADMAP_ID, 0, 10);

        self::assertEquals(4, $collection->getTotalSize());
        self::assertCount(2, $collection->getRepresentations());
        self::assertEquals(
            [
                new TaskRepresentation(
                    201,
                    'task #201',
                    '/plugins/tracker/?aid=201',
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
                    new ProjectReference($this->project),
                ),
                new TaskRepresentation(
                    203,
                    'task #203',
                    '/plugins/tracker/?aid=203',
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
                    new ProjectReference($this->project),
                ),
            ],
            $collection->getRepresentations()
        );
    }

    public function testItReturnsAPaginatedListOfReadableTaskRepresentationFromReport(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->getTracker(self::TRACKER_ID, $title_field, 'acid-green', 'task');

        $start_date_field = DateFieldBuilder::aDateField(1)
            ->withReadPermission($this->user, true)
            ->build();
        $end_date_field   = DateFieldBuilder::aDateField(2)
            ->withReadPermission($this->user, true)
            ->build();

        $total_effort_field = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $total_effort_field->method('userCanRead')->willReturn(true);
        $remaining_effort_field = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $remaining_effort_field->method('userCanRead')->willReturn(true);

        $semantic_timeframe = new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date_field, $end_date_field));
        $this->semantic_timeframe_builder
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn($semantic_timeframe);

        $this->progress_builder
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn(
                new SemanticProgress(
                    $tracker,
                    new MethodBasedOnEffort(
                        $this->createMock(SemanticProgressDao::class),
                        $total_effort_field,
                        $remaining_effort_field
                    )
                )
            );

        $task_201 = $this->anArtifact(201, 'Do this', $tracker, true, $semantic_timeframe);
        $task_202 = $this->anArtifact(202, 'Do that', $tracker, false, $semantic_timeframe);
        $task_203 = $this->anArtifactWithoutStartDate(203, 'Do those', $tracker, $semantic_timeframe);
        $task_204 = $this->anArtifact(204, 'Done more than 1 year ago', $tracker, true, $semantic_timeframe);

        $this->mockEffort($total_effort_field, [201 => 8, 203 => 3]);
        $this->mockEffort($remaining_effort_field, [201 => 5, 203 => 0.75]);

        $artifacts = [$task_201, $task_202, $task_203, $task_204];
        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByListOfArtifactIds')
            ->with([201, 202], 0, 10)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts($artifacts, 4)
            );

        $this->tasks_filter
            ->expects(self::once())
            ->method('filterOutOfDateArtifacts')
            ->willReturn([$task_201, $task_202, $task_203]);

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
            ->getRetriever(
                RetrieveTrackerStub::withTracker($tracker),
                $dependency_retriever,
                RetrieveReportToFilterArtifactsStub::withReport($report)
            )
            ->getTasks(self::ROADMAP_ID, 0, 10);

        self::assertEquals(4, $collection->getTotalSize());
        self::assertCount(2, $collection->getRepresentations());
        self::assertEquals(
            [
                new TaskRepresentation(
                    201,
                    'task #201',
                    '/plugins/tracker/?aid=201',
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
                    new ProjectReference($this->project),
                ),
                new TaskRepresentation(
                    203,
                    'task #203',
                    '/plugins/tracker/?aid=203',
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
                    new ProjectReference($this->project),
                ),
            ],
            $collection->getRepresentations()
        );
    }

    public function testItExcludesArtifactsThatAreChildOfAnArtifactOfTheSameTracker(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([self::TRACKER_ID]);


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);

        $tracker = $this->getTracker(self::TRACKER_ID, $title_field, 'acid-green', 'task');

        $start_date_field   = DateFieldBuilder::aDateField(1)
            ->withReadPermission($this->user, true)
            ->build();
        $end_date_field     = DateFieldBuilder::aDateField(2)
            ->withReadPermission($this->user, true)
            ->build();
        $total_effort_field = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $total_effort_field->method('userCanRead')->willReturn(true);
        $remaining_effort_field = $this->createMock(\Tracker_FormElement_Field_Numeric::class);
        $remaining_effort_field->method('userCanRead')->willReturn(true);

        $semantic_timeframe = new SemanticTimeframe($tracker, new TimeframeWithEndDate($start_date_field, $end_date_field));
        $this->semantic_timeframe_builder
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn($semantic_timeframe);

        $this->progress_builder
            ->method('getSemantic')
            ->with($tracker)
            ->willReturn(
                new SemanticProgress(
                    $tracker,
                    new MethodBasedOnEffort(
                        $this->createMock(SemanticProgressDao::class),
                        $total_effort_field,
                        $remaining_effort_field
                    )
                )
            );

        $task_201 = $this->anArtifact(201, 'Do this', $tracker, true, $semantic_timeframe);
        $task_202 = $this->anArtifact(202, 'Do that', $tracker, false, $semantic_timeframe);

        $this->mockEffort($total_effort_field, [201 => 8, 202 => 3]);
        $this->mockEffort($remaining_effort_field, [201 => 5, 202 => 0.75]);

        $artifacts = [$task_201, $task_202];
        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByListOfTrackerIds')
            ->with([self::TRACKER_ID], 0, 10)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts($artifacts, 2)
            );

        $this->tasks_filter
            ->expects(self::once())
            ->method('filterOutOfDateArtifacts')
            ->willReturn([$task_201, $task_202]);

        $dependency_retriever = new class implements IRetrieveDependencies {
            public function getDependencies(Artifact $artifact): array
            {
                return [];
            }
        };

        $collection = $this
            ->getRetriever(RetrieveTrackerStub::withTracker($tracker), $dependency_retriever, RetrieveReportToFilterArtifactsStub::withoutReport())
            ->getTasks(self::ROADMAP_ID, 0, 10);

        self::assertEquals(2, $collection->getTotalSize());
        self::assertCount(1, $collection->getRepresentations());
        self::assertEquals(
            [
                new TaskRepresentation(
                    201,
                    'task #201',
                    '/plugins/tracker/?aid=201',
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
                    new ProjectReference($this->project),
                ),
            ],
            $collection->getRepresentations()
        );
    }

    public function testItReturnsAPaginatedListOfReadableTaskRepresentationBelongingToDifferentTrackers(): void
    {
        $this->dao
            ->expects(self::once())
            ->method('searchById')
            ->with(self::ROADMAP_ID)
            ->willReturn(
                [
                    'id' => self::ROADMAP_ID,
                    'owner_id' => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title' => 'My Roadmap',
                ]
            );
        $this->dao
            ->method('searchSelectedTrackers')
            ->with(self::ROADMAP_ID)
            ->willReturn([
                self::TRACKER_ID,
                self::ANOTHER_TRACKER_ID,
            ]);


        $this->url_verification
            ->expects(self::once())
            ->method('userCanAccessProject')
            ->with($this->user, $this->project);

        $title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $title_field->method('userCanRead')->willReturn(true);
        $another_title_field = $this->createMock(\Tracker_FormElement_Field_String::class);
        $another_title_field->method('userCanRead')->willReturn(true);

        $tracker         = $this->getTracker(self::TRACKER_ID, $title_field, 'acid-green', 'task');
        $another_tracker = $this->getTracker(self::ANOTHER_TRACKER_ID, $another_title_field, 'red-wine', 'bug');

        $start_date_field = DateFieldBuilder::aDateField(1)
            ->withReadPermission($this->user, true)
            ->build();
        $end_date_field   = DateFieldBuilder::aDateField(2)
            ->withReadPermission($this->user, true)
            ->build();

        $another_start_date_field = DateFieldBuilder::aDateField(3)
            ->withReadPermission($this->user, true)
            ->build();
        $another_end_date_field   = DateFieldBuilder::aDateField(4)
            ->withReadPermission($this->user, true)
            ->build();

        $semantic_timeframe_tracker         = new SemanticTimeframe(
            $tracker,
            new TimeframeWithEndDate($start_date_field, $end_date_field)
        );
        $semantic_timeframe_another_tracker = new SemanticTimeframe(
            $another_tracker,
            new TimeframeWithEndDate($another_start_date_field, $another_end_date_field)
        );
        $this->semantic_timeframe_builder
            ->method('getSemantic')
            ->willReturnCallback(static fn(Tracker $arg) => match ($arg) {
                $tracker         => $semantic_timeframe_tracker,
                $another_tracker => $semantic_timeframe_another_tracker
            });

        $this->progress_builder
            ->method('getSemantic')
            ->willReturnCallback(static fn(Tracker $arg) => match ($arg) {
                $tracker         => new SemanticProgress($tracker, new MethodNotConfigured()),
                $another_tracker => new SemanticProgress($another_tracker, new MethodNotConfigured()),
            });

        $task_201 = $this->anArtifact(201, 'Do this', $tracker, true, $semantic_timeframe_tracker);
        $task_203 = $this->anArtifactWithoutStartDate(203, 'Do those', $another_tracker, $semantic_timeframe_another_tracker);


        $artifacts = [$task_201, $task_203];
        $this->artifact_factory
            ->expects(self::once())
            ->method('getPaginatedArtifactsByListOfTrackerIds')
            ->with([self::TRACKER_ID, self::ANOTHER_TRACKER_ID], 0, 10)
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts($artifacts, 2)
            );

        $this->tasks_filter
            ->expects(self::once())
            ->method('filterOutOfDateArtifacts')
            ->willReturn([$task_201, $task_203]);

        $dependency_retriever = new class implements IRetrieveDependencies {
            public function getDependencies(Artifact $artifact): array
            {
                return [];
            }
        };

        $collection = $this
            ->getRetriever(
                RetrieveTrackerStub::withTrackers($tracker, $another_tracker),
                $dependency_retriever,
                RetrieveReportToFilterArtifactsStub::withoutReport()
            )
            ->getTasks(self::ROADMAP_ID, 0, 10);

        self::assertEquals(2, $collection->getTotalSize());
        self::assertCount(2, $collection->getRepresentations());
        self::assertEquals(
            [
                new TaskRepresentation(
                    201,
                    'task #201',
                    '/plugins/tracker/?aid=201',
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
                    new ProjectReference($this->project),
                ),
                new TaskRepresentation(
                    203,
                    'bug #203',
                    '/plugins/tracker/?aid=203',
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
                    new ProjectReference($this->project),
                ),
            ],
            $collection->getRepresentations()
        );
    }

    private function mockDate(MockObject&\Tracker_FormElement_Field_Date $date_field, array $values): void
    {
        $date_field
            ->method('getLastChangesetValue')
            ->willReturnCallback(
                fn(Artifact $artifact) => $this->getChangesetValueDate($artifact, $date_field, $values)
            );
    }

    private function mockEffort(MockObject&\Tracker_FormElement_Field_Numeric $effort_field, array $values): void
    {
        $effort_field
            ->method('getLastChangesetValue')
            ->willReturnCallback(
                fn(Artifact $artifact) => $this->getChangesetValueFloat($artifact, $effort_field, $values)
            );
    }

    private function getChangesetValueDate(Artifact $artifact, \Tracker_FormElement_Field_Date $field, array $values): ?\Tracker_Artifact_ChangesetValue_Date
    {
        if (! isset($values[$artifact->getId()])) {
            return null;
        }

        return new \Tracker_Artifact_ChangesetValue_Date(
            1,
            $this->createMock(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            $values[$artifact->getId()],
        );
    }

    private function getChangesetValueFloat(Artifact $artifact, \Tracker_FormElement_Field_Numeric $field, array $values): ?\Tracker_Artifact_ChangesetValue_Float
    {
        if (! isset($values[$artifact->getId()])) {
            return null;
        }

        return new \Tracker_Artifact_ChangesetValue_Float(
            1,
            $this->createMock(\Tracker_Artifact_Changeset::class),
            $field,
            false,
            $values[$artifact->getId()],
        );
    }

    private function anArtifact(int $id, string $title, Tracker $tracker, bool $readable, SemanticTimeframe $semantic_timeframe): Artifact
    {
        $changeset   = ChangesetTestBuilder::aChangeset('1')->build();
        $start_field = $semantic_timeframe->getStartDateField();
        if ($start_field !== null) {
            $changeset->setFieldValue(
                $start_field,
                ChangesetValueDateTestBuilder::aValue(1, $changeset, $start_field)
                    ->withTimestamp(1234567890)
                    ->build()
            );
        }
        $end_field = $semantic_timeframe->getEndDateField();
        if ($end_field !== null) {
            $changeset->setFieldValue(
                $end_field,
                ChangesetValueDateTestBuilder::aValue(2, $changeset, $end_field)
                    ->withTimestamp(1234567890)
                    ->build()
            );
        }

        $artifact_test_builder = ArtifactTestBuilder::anArtifact($id)
            ->withTitle($title)
            ->inTracker($tracker)
            ->withChangesets($changeset)
            ->withParent(null)
            ->isOpen(true);

        return $readable
            ? $artifact_test_builder->userCanView($this->user)->build()
            : $artifact_test_builder->userCannotView($this->user)->build();
    }

    private function anArtifactWithoutStartDate(int $id, string $title, Tracker $tracker, SemanticTimeframe $semantic_timeframe): Artifact
    {
        $changeset   = ChangesetTestBuilder::aChangeset('1')->build();
        $start_field = $semantic_timeframe->getStartDateField();
        if ($start_field !== null) {
            $changeset->setFieldValue($start_field, null);
        }
        $end_field = $semantic_timeframe->getEndDateField();
        if ($end_field !== null) {
            $changeset->setFieldValue(
                $end_field,
                ChangesetValueDateTestBuilder::aValue(2, $changeset, $end_field)
                    ->withTimestamp(1234567890)
                    ->build()
            );
        }
        return ArtifactTestBuilder::anArtifact($id)
            ->withTitle($title)
            ->inTracker($tracker)
            ->withChangesets($changeset)
            ->userCanView($this->user)
            ->withParent(null)
            ->isOpen(true)
            ->build();
    }
}
