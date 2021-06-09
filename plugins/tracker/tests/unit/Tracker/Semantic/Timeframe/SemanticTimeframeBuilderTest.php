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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;

class SemanticTimeframeBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private const STORY_TRACKER_ID = 42;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticTimeframeDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $story_tracker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LinksRetriever
     */
    private $links_retriever;

    protected function setUp(): void
    {
        $this->dao                  = Mockery::mock(SemanticTimeframeDao::class);
        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);
        $this->tracker_factory      = Mockery::mock(\TrackerFactory::class);
        $this->links_retriever      = Mockery::mock(LinksRetriever::class);
        $this->story_tracker        = Mockery::mock(Tracker::class, (['getId' => self::STORY_TRACKER_ID]));
    }

    public function testItBuildsANotConfiguredSemantic(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->once()
            ->andReturn(null);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe($this->story_tracker, new TimeframeNotConfigured()),
            $builder->getSemantic($this->story_tracker)
        );
    }

    public function testItBuildsASemanticWithEndDate(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->once()
            ->andReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => null,
                'end_date_field_id' => 104,
                'implied_from_tracker_id' => null
            ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $end_date_field   = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $this->form_element_factory->shouldReceive('getUsedDateFieldById')
                ->with($this->story_tracker, 101)
                ->once()
                ->andReturn($start_date_field);

        $this->form_element_factory->shouldReceive('getUsedDateFieldById')
            ->with($this->story_tracker, 104)
            ->once()
            ->andReturn($end_date_field);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe($this->story_tracker, new TimeframeWithEndDate($start_date_field, $end_date_field)),
            $builder->getSemantic($this->story_tracker)
        );
    }

    public function testItBuildsASemanticWithDuration(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->once()
            ->andReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => 104,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => null
            ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration_field   = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);

        $this->form_element_factory->shouldReceive('getUsedDateFieldById')
            ->with($this->story_tracker, 101)
            ->once()
            ->andReturn($start_date_field);

        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with($this->story_tracker, 104, ['int', 'float', 'computed'])
            ->once()
            ->andReturn($duration_field);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe($this->story_tracker, new TimeframeWithDuration($start_date_field, $duration_field)),
            $builder->getSemantic($this->story_tracker)
        );
    }

    public function testItReturnsANotConfiguredSemanticIfThereIsNoDurationNorEndDateField(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->once()
            ->andReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => null,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => null
            ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $this->form_element_factory->shouldReceive('getUsedDateFieldById')
            ->with($this->story_tracker, 101)
            ->once()
            ->andReturn($start_date_field);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertFalse($builder->getSemantic($this->story_tracker)->isDefined());
    }

    public function testItShouldReturnANotConfiguredSemanticIfTrackerDoesNotExist(): void
    {
        $this->dao->shouldReceive('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->once()
            ->andReturn([
                'start_date_field_id' => null,
                'duration_field_id' => null,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => 123
            ]);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(123)
            ->once()
            ->andReturn(null);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertFalse($builder->getSemantic($this->story_tracker)->isDefined());
    }

    public function testItShouldReturnASemanticTimeframeImplied(): void
    {
        $implied_from_tracker_id = 123;

        $this->dao->shouldReceive('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->once()
            ->andReturn(
                [
                    'start_date_field_id' => null,
                    'duration_field_id' => null,
                    'end_date_field_id' => null,
                    'implied_from_tracker_id' => $implied_from_tracker_id
                ]
            );

        $implied_from_tracker = \Mockery::mock(\Tracker::class, ['getId' => $implied_from_tracker_id]);
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with($implied_from_tracker_id)
            ->once()
            ->andReturn(
                $implied_from_tracker
            );

        $this->dao->shouldReceive('searchByTrackerId')
            ->with($implied_from_tracker_id)
            ->once()
            ->andReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => 104,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => null
            ]);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration_field   = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);

        $this->form_element_factory->shouldReceive('getUsedDateFieldById')
            ->with($implied_from_tracker, 101)
            ->once()
            ->andReturn($start_date_field);

        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with($implied_from_tracker, 104, ['int', 'float', 'computed'])
            ->once()
            ->andReturn($duration_field);

        $semantic_implied_from_tracker = new SemanticTimeframe(
            $implied_from_tracker,
            new TimeframeWithDuration($start_date_field, $duration_field)
        );

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe(
                $this->story_tracker,
                new TimeframeImpliedFromAnotherTracker(
                    $this->story_tracker,
                    $semantic_implied_from_tracker,
                    $this->links_retriever
                )
            ),
            $builder->getSemantic($this->story_tracker)
        );
    }

    public function testItShouldNotReturnASemanticTimeframeImpliedWhenTargetTrackerSemanticIsNotDefined(): void
    {
        $release_tracker_id = 123;
        $release_tracker    = \Mockery::mock(\Tracker::class, ['getId' => $release_tracker_id]);

        $this->dao->shouldReceive('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->once()
            ->andReturn([
                'start_date_field_id' => null,
                'duration_field_id' => null,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => $release_tracker_id
            ]);

        $this->dao->shouldReceive('searchByTrackerId')
            ->with($release_tracker_id)
            ->once()
            ->andReturn(null);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with($release_tracker_id)
            ->once()
            ->andReturn($release_tracker);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe(
                $this->story_tracker,
                new TimeframeNotConfigured()
            ),
            $builder->getSemantic($this->story_tracker)
        );
    }

    public function testItShouldNotReturnASemanticTimeframeImpliedWhenTargetTrackerSemanticIsAlreadyImpliedFromAnotherTracker(): void
    {
        $release_tracker_id = 123;
        $epic_tracker_id    = 456;

        $release_tracker = \Mockery::mock(\Tracker::class, ['getId' => $release_tracker_id]);
        $epic_tracker    = Mockery::mock(\Tracker::class, ['getId' => $epic_tracker_id]);

        $this->dao->shouldReceive('searchByTrackerId')
            ->with(self::STORY_TRACKER_ID)
            ->once()
            ->andReturn([
                'start_date_field_id' => null,
                'duration_field_id' => null,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => $release_tracker_id
            ]);

        $this->dao->shouldReceive('searchByTrackerId')
            ->with($release_tracker_id)
            ->once()
            ->andReturn([
                'start_date_field_id' => null,
                'duration_field_id' => null,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => $epic_tracker_id
            ]);

        $this->dao->shouldReceive('searchByTrackerId')
            ->with($epic_tracker_id)
            ->once()
            ->andReturn([
                'start_date_field_id' => 101,
                'duration_field_id' => 102,
                'end_date_field_id' => null,
                'implied_from_tracker_id' => null
            ]);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with($release_tracker_id)
            ->once()
            ->andReturn($release_tracker);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with($epic_tracker_id)
            ->once()
            ->andReturn($epic_tracker);

        $start_date_field = Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $duration_field   = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);

        $this->form_element_factory->shouldReceive('getUsedDateFieldById')
            ->with($epic_tracker, 101)
            ->once()
            ->andReturn($start_date_field);

        $this->form_element_factory->shouldReceive('getUsedFieldByIdAndType')
            ->with($epic_tracker, 102, ['int', 'float', 'computed'])
            ->once()
            ->andReturn($duration_field);

        $builder = new SemanticTimeframeBuilder($this->dao, $this->form_element_factory, $this->tracker_factory, $this->links_retriever);
        $this->assertEquals(
            new SemanticTimeframe(
                $this->story_tracker,
                new TimeframeNotConfigured()
            ),
            $builder->getSemantic($this->story_tracker)
        );
    }
}
