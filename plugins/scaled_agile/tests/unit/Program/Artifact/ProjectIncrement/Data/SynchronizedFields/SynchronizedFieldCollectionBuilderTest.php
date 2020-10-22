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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Backlog\CreationCheck\MissingTimeFrameFieldException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Description\NoDescriptionFieldException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Status\NoStatusFieldException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Title\NoTitleFieldException;
use Tuleap\ScaledAgile\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\TrackerColor;

final class SynchronizedFieldCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SynchronizedFieldCollectionBuilder
     */
    private $builder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_Semantic_TitleFactory
     */
    private $title_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_Semantic_DescriptionFactory
     */
    private $description_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_Semantic_StatusFactory
     */
    private $status_factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SemanticTimeframeBuilder
     */
    private $time_frame_builder;

    protected function setUp(): void
    {
        $this->form_element_factory = M::mock(\Tracker_FormElementFactory::class);
        $this->title_factory        = M::mock(\Tracker_Semantic_TitleFactory::class);
        $this->description_factory  = M::mock(\Tracker_Semantic_DescriptionFactory::class);
        $this->status_factory       = M::mock(\Tracker_Semantic_StatusFactory::class);
        $this->time_frame_builder   = M::mock(SemanticTimeframeBuilder::class);
        $this->builder              = new SynchronizedFieldCollectionBuilder(
            new SynchronizedFieldsGatherer(
                $this->form_element_factory,
                $this->title_factory,
                $this->description_factory,
                $this->status_factory,
                $this->time_frame_builder
            )
        );
    }

    public function testBuildFromMilestoneTrackersReturnsACollection(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new SourceTrackerCollection([$first_tracker, $second_tracker]);
        $this->mockArtifactLinkField($first_tracker);
        $this->mockArtifactLinkField($second_tracker);
        $this->mockTitleField($first_tracker);
        $this->mockTitleField($second_tracker);
        $this->mockDescriptionField($first_tracker);
        $this->mockDescriptionField($second_tracker);
        $this->mockStatusField($first_tracker);
        $this->mockStatusField($second_tracker);
        $this->mockTimeFrameFields($first_tracker);
        $this->mockTimeFrameFields($second_tracker);

        $this->assertNotNull($this->builder->buildFromSourceTrackers($milestones));
    }

    public function testBuildFromMilestoneTrackersAcceptsEndDateFields(): void
    {
        $tracker    = $this->buildTestTracker(103);
        $milestones = new SourceTrackerCollection([$tracker]);
        $this->mockArtifactLinkField($tracker);
        $this->mockTitleField($tracker);
        $this->mockDescriptionField($tracker);
        $this->mockStatusField($tracker);
        $start_date_field    = M::mock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn('801');
        $end_date_field      = M::mock(\Tracker_FormElement_Field_Date::class);
        $end_date_field->shouldReceive('getId')->andReturn('802');
        $time_frame_semantic = new SemanticTimeframe($tracker, $start_date_field, null, $end_date_field);
        $this->time_frame_builder->shouldReceive('getSemantic')
            ->once()
            ->with($tracker)
            ->andReturn($time_frame_semantic);

        $this->assertNotNull($this->builder->buildFromSourceTrackers($milestones));
    }

    public function testItThrowsWhenOneTrackerDoesNotHaveAnArtifactLinkField(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new SourceTrackerCollection([$first_tracker, $second_tracker]);
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->once()
            ->with($first_tracker)
            ->andReturn([]);

        $this->expectException(NoArtifactLinkFieldException::class);
        $this->builder->buildFromSourceTrackers($milestones);
    }

    public function testItThrowsWhenOneTrackerDoesNotHaveATitleField(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new SourceTrackerCollection([$first_tracker, $second_tracker]);
        $this->mockArtifactLinkField($first_tracker);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturnNull();
        $this->title_factory->shouldReceive('getByTracker')
            ->once()
            ->with($first_tracker)
            ->andReturn($title_semantic);

        $this->expectException(NoTitleFieldException::class);
        $this->builder->buildFromSourceTrackers($milestones);
    }

    public function testItThrowsWhenOneTrackerDoesNotHaveADescriptionField(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new SourceTrackerCollection([$first_tracker, $second_tracker]);
        $this->mockArtifactLinkField($first_tracker);
        $this->mockTitleField($first_tracker);
        $description_semantic = M::mock(\Tracker_Semantic_Description::class);
        $description_semantic->shouldReceive('getField')->andReturnNull();
        $this->description_factory->shouldReceive('getByTracker')
            ->once()
            ->with($first_tracker)
            ->andReturn($description_semantic);

        $this->expectException(NoDescriptionFieldException::class);
        $this->builder->buildFromSourceTrackers($milestones);
    }

    public function testItThrowsWhenOneTrackerDoesNotHaveAStatusField(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new SourceTrackerCollection([$first_tracker, $second_tracker]);
        $this->mockArtifactLinkField($first_tracker);
        $this->mockTitleField($first_tracker);
        $this->mockDescriptionField($first_tracker);
        $status_semantic = M::mock(\Tracker_Semantic_Status::class);
        $status_semantic->shouldReceive('getField')->andReturnNull();
        $this->status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($first_tracker)
            ->andReturn($status_semantic);

        $this->expectException(NoStatusFieldException::class);
        $this->builder->buildFromSourceTrackers($milestones);
    }

    public function testItThrowsWhenOneTrackerDoesNotHaveAStartDateField(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new SourceTrackerCollection([$first_tracker, $second_tracker]);
        $this->mockArtifactLinkField($first_tracker);
        $this->mockTitleField($first_tracker);
        $this->mockDescriptionField($first_tracker);
        $this->mockStatusField($first_tracker);
        $time_frame_semantic = new SemanticTimeframe($first_tracker, null, null, null);
        $this->time_frame_builder->shouldReceive('getSemantic')
            ->once()
            ->with($first_tracker)
            ->andReturn($time_frame_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->builder->buildFromSourceTrackers($milestones);
    }

    public function testItThrowsWhenOneTrackerHasNeitherEndDateNorDuration(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new SourceTrackerCollection([$first_tracker, $second_tracker]);
        $this->mockArtifactLinkField($first_tracker);
        $this->mockTitleField($first_tracker);
        $this->mockDescriptionField($first_tracker);
        $this->mockStatusField($first_tracker);
        $start_date_field    = M::mock(\Tracker_FormElement_Field_Date::class);
        $time_frame_semantic = new SemanticTimeframe($first_tracker, $start_date_field, null, null);
        $this->time_frame_builder->shouldReceive('getSemantic')
            ->once()
            ->with($first_tracker)
            ->andReturn($time_frame_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->builder->buildFromSourceTrackers($milestones);
    }

    private function buildTestTracker(int $tracker_id): \Tracker
    {
        return new \Tracker(
            $tracker_id,
            null,
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            TrackerColor::default(),
            false
        );
    }

    private function mockArtifactLinkField(\Tracker $tracker): void
    {
        $artifact_link_field = M::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link_field->shouldReceive('getId')->andReturn('741');
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->once()
            ->with($tracker)
            ->andReturn([$artifact_link_field]);
    }

    private function mockTitleField(\Tracker $tracker): void
    {
        $title_field    = M::mock(\Tracker_FormElement_Field_String::class);
        $title_field->shouldReceive('getId')->andReturn('742');
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturn($title_field);
        $this->title_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($title_semantic);
    }

    private function mockDescriptionField(\Tracker $tracker): void
    {
        $description_field    = M::mock(\Tracker_FormElement_Field_Text::class);
        $description_field->shouldReceive('getId')->andReturn('743');
        $description_semantic = M::mock(\Tracker_Semantic_Description::class);
        $description_semantic->shouldReceive('getField')->andReturn($description_field);
        $this->description_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($description_semantic);
    }

    private function mockStatusField(\Tracker $tracker): void
    {
        $status_field    = M::mock(\Tracker_FormElement_Field_List::class);
        $status_field->shouldReceive('getId')->andReturn('744');
        $status_semantic = M::mock(\Tracker_Semantic_Status::class);
        $status_semantic->shouldReceive('getField')->andReturn($status_field);
        $this->status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($status_semantic);
    }

    private function mockTimeFrameFields(\Tracker $tracker): void
    {
        $start_date_field    = M::mock(\Tracker_FormElement_Field_Date::class);
        $start_date_field->shouldReceive('getId')->andReturn('745');
        $duration_field      = M::mock(\Tracker_FormElement_Field_Numeric::class);
        $duration_field->shouldReceive('getId')->andReturn('746');
        $time_frame_semantic = new SemanticTimeframe($tracker, $start_date_field, $duration_field, null);
        $this->time_frame_builder->shouldReceive('getSemantic')
            ->once()
            ->with($tracker)
            ->andReturn($time_frame_semantic);
    }
}
