<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SemanticTimeframeBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const STORY_TRACKER_ID = 42;

    private MockObject|SemanticTimeframeDao $dao;
    private MockObject|Tracker_FormElementFactory $form_element_factory;
    private MockObject|\TrackerFactory $tracker_factory;
    private Tracker $story_tracker;
    private MockObject|LinksRetriever $links_retriever;

    protected function setUp(): void
    {
        $this->dao                  = $this->createMock(SemanticTimeframeDao::class);
        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->tracker_factory      = $this->createMock(\TrackerFactory::class);
        $this->links_retriever      = $this->createMock(LinksRetriever::class);
        $this->story_tracker        = TrackerTestBuilder::aTracker()->withId(self::STORY_TRACKER_ID)->build();
    }

    public function testItBuildsANotConfiguredSemantic(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->willReturn(null);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe($this->story_tracker, new TimeframeNotConfigured()),
            $builder->getSemantic($this->story_tracker)
        );
    }

    public function testItBuildsASemanticWithEndDate(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->willReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => null,
                'end_date_field_id' => 104,
                'implied_from_tracker_id' => null,
            ]);

        $start_date_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $end_date_field   = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);

        $this->form_element_factory
            ->expects($this->exactly(2))
            ->method('getUsedDateFieldById')
            ->willReturnCallback(fn (Tracker $tracker, $field_id) => match (true) {
                $tracker === $this->story_tracker && $field_id === 101 => $start_date_field,
                $tracker === $this->story_tracker && $field_id === 104 => $end_date_field,
            });

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe($this->story_tracker, new TimeframeWithEndDate($start_date_field, $end_date_field)),
            $builder->getSemantic($this->story_tracker)
        );
    }

    public function testItBuildsASemanticWithDuration(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->willReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => 104,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => null,
            ]);

        $start_date_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $duration_field   = $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedDateFieldById')
            ->with($this->story_tracker, 101)
            ->willReturn($start_date_field);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedFieldByIdAndType')
            ->with($this->story_tracker, 104, ['int', 'float', 'computed'])
            ->willReturn($duration_field);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe($this->story_tracker, new TimeframeWithDuration($start_date_field, $duration_field)),
            $builder->getSemantic($this->story_tracker)
        );
    }

    public function testItReturnsANotConfiguredSemanticIfThereIsNoDurationNorEndDateField(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->willReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => null,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => null,
            ]);

        $start_date_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedDateFieldById')
            ->with($this->story_tracker, 101)
            ->willReturn($start_date_field);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertFalse($builder->getSemantic($this->story_tracker)->isDefined());
    }

    public function testItShouldReturnANotConfiguredSemanticIfTrackerDoesNotExist(): void
    {
        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->willReturn([
                'start_date_field_id' => null,
                'duration_field_id' => null,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => 123,
            ]);

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->with(123)
            ->willReturn(null);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertFalse($builder->getSemantic($this->story_tracker)->isDefined());
    }

    public function testItShouldReturnASemanticTimeframeImplied(): void
    {
        $project_id    = 500;
        $story_project = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $story_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::STORY_TRACKER_ID)
            ->withProject($story_project)
            ->build();

        $implied_from_tracker_id = 123;
        $implied_from_tracker    = TrackerTestBuilder::aTracker()
            ->withId($implied_from_tracker_id)
            ->withProject($story_project)
            ->build();

        $this->dao
            ->expects($this->exactly(2))
            ->method('searchByTrackerId')
            ->willReturnCallback(static fn (int $tracker_id) => match ($tracker_id) {
                self::STORY_TRACKER_ID => [
                    'start_date_field_id' => null,
                    'duration_field_id' => null,
                    'end_date_field_id' => null,
                    'implied_from_tracker_id' => $implied_from_tracker_id,
                ],
                $implied_from_tracker_id => [
                    'start_date_field_id' => 101,
                    'duration_field_id' => 104,
                    'end_date_field_id' => null,
                    'implied_from_tracker_id' => null,
                ]
            });

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->with($implied_from_tracker_id)
            ->willReturn(
                $implied_from_tracker
            );

        $start_date_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $duration_field   = $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedDateFieldById')
            ->with($implied_from_tracker, 101)
            ->willReturn($start_date_field);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedFieldByIdAndType')
            ->with($implied_from_tracker, 104, ['int', 'float', 'computed'])
            ->willReturn($duration_field);

        $semantic_implied_from_tracker = new SemanticTimeframe(
            $implied_from_tracker,
            new TimeframeWithDuration($start_date_field, $duration_field)
        );

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe(
                $story_tracker,
                new TimeframeImpliedFromAnotherTracker(
                    $story_tracker,
                    $semantic_implied_from_tracker,
                    $this->links_retriever
                )
            ),
            $builder->getSemantic($story_tracker)
        );
    }

    public function testItShouldNotReturnASemanticTimeframeImpliedWhenTargetTrackerSemanticIsNotDefined(): void
    {
        $project_id    = 500;
        $story_project = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $story_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::STORY_TRACKER_ID)
            ->withProject($story_project)
            ->build();

        $release_tracker_id = 123;
        $release_tracker    = TrackerTestBuilder::aTracker()
            ->withId($release_tracker_id)
            ->withProject($story_project)
            ->build();

        $this->dao
            ->expects($this->exactly(2))
            ->method('searchByTrackerId')
            ->willReturnCallback(static fn (int $tracker_id) => match ($tracker_id) {
                self::STORY_TRACKER_ID => [
                    'start_date_field_id' => null,
                    'duration_field_id' => null,
                    'end_date_field_id' => null,
                    'implied_from_tracker_id' => $release_tracker_id,
                ],
                $release_tracker_id => null,
            });

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->with($release_tracker_id)
            ->willReturn($release_tracker);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe(
                $story_tracker,
                new TimeframeNotConfigured()
            ),
            $builder->getSemantic($story_tracker)
        );
    }

    public function testItShouldNotReturnASemanticTimeframeImpliedWhenTargetTrackerSemanticIsAlreadyImpliedFromAnotherTracker(): void
    {
        $project_id    = 500;
        $story_project = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $story_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::STORY_TRACKER_ID)
            ->withProject($story_project)
            ->build();

        $release_tracker_id = 123;
        $release_tracker    = TrackerTestBuilder::aTracker()
            ->withId($release_tracker_id)
            ->withProject($story_project)
            ->build();

        $epic_tracker_id = 456;
        $epic_tracker    = TrackerTestBuilder::aTracker()
            ->withId($epic_tracker_id)
            ->withProject($story_project)
            ->build();

        $this->dao
            ->expects($this->exactly(3))
            ->method('searchByTrackerId')
            ->willReturnCallback(static fn (int $tracker_id) => match ($tracker_id) {
                self::STORY_TRACKER_ID => [
                    'start_date_field_id' => null,
                    'duration_field_id' => null,
                    'end_date_field_id' => null,
                    'implied_from_tracker_id' => $release_tracker_id,
                ],
                $release_tracker_id => [
                    'start_date_field_id' => null,
                    'duration_field_id' => null,
                    'end_date_field_id' => null,
                    'implied_from_tracker_id' => $epic_tracker_id,
                ],
                $epic_tracker_id => [
                    'start_date_field_id' => 101,
                    'duration_field_id' => 102,
                    'end_date_field_id' => null,
                    'implied_from_tracker_id' => null,
                ],
            });

        $this->tracker_factory
            ->expects($this->exactly(2))
            ->method('getTrackerById')
            ->willReturnCallback(static fn (int $tracker_id) => match ($tracker_id) {
                $release_tracker_id => $release_tracker,
                $epic_tracker_id => $epic_tracker,
            });

        $start_date_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Date\DateField::class);
        $duration_field   = $this->createMock(\Tuleap\Tracker\FormElement\Field\NumericField::class);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedDateFieldById')
            ->with($epic_tracker, 101)
            ->willReturn($start_date_field);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getUsedFieldByIdAndType')
            ->with($epic_tracker, 102, ['int', 'float', 'computed'])
            ->willReturn($duration_field);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe(
                $story_tracker,
                new TimeframeNotConfigured()
            ),
            $builder->getSemantic($story_tracker)
        );
    }

    public function testItBuildsAConfigInvalidSemantic(): void
    {
        $implied_from_tracker_id = 123;

        $this->dao
            ->expects($this->once())
            ->method('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->willReturn(
                [
                    'start_date_field_id' => null,
                    'duration_field_id' => null,
                    'end_date_field_id' => null,
                    'implied_from_tracker_id' => $implied_from_tracker_id,
                ]
            );

        $implied_from_tracker_project = ProjectTestBuilder::aProject()->withId(12)->build();

        $implied_from_tracker = TrackerTestBuilder::aTracker()
            ->withId($implied_from_tracker_id)
            ->withProject($implied_from_tracker_project)
            ->build();

        $this->tracker_factory
            ->expects($this->once())
            ->method('getTrackerById')
            ->with($implied_from_tracker_id)
            ->willReturn(
                $implied_from_tracker
            );

        $story_project = ProjectTestBuilder::aProject()->withId(13)->build();
        $story_tracker = TrackerTestBuilder::aTracker()
            ->withId(self::STORY_TRACKER_ID)
            ->withProject($story_project)
            ->build();

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);

        $this->assertEquals(
            new SemanticTimeframe(
                $story_tracker,
                new TimeframeConfigInvalid()
            ),
            $builder->getSemantic($story_tracker)
        );
    }
}
