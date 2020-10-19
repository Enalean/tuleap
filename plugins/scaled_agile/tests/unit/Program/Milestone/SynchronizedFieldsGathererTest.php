<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Milestone;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class SynchronizedFieldsGathererTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SynchronizedFieldsGatherer
     */
    private $gatherer;
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
        $this->gatherer               = new SynchronizedFieldsGatherer(
            $this->form_element_factory,
            $this->title_factory,
            $this->description_factory,
            $this->status_factory,
            $this->time_frame_builder
        );
    }

    public function testItReturnsTargetFields(): void
    {
        $tracker             = $this->buildTestTracker(27);
        $artifact_link_field = $this->mockArtifactLinkField($tracker);
        $title_field         = $this->mockTitleField($tracker);
        $description_field   = $this->mockDescriptionField($tracker);
        $status_field        = $this->mockStatusField($tracker);
        [$start_date_field, $duration_field] = $this->mockTimeFrameFields($tracker);

        $fields = $this->gatherer->gather($tracker);

        self::assertSame($artifact_link_field, $fields->getArtifactLinkField());
        self::assertSame($title_field, $fields->getTitleField());
        self::assertSame($description_field, $fields->getDescriptionField());
        self::assertSame($status_field, $fields->getStatusField());
        self::assertSame($start_date_field, $fields->getTimeframeFields()->getStartDateField());
        self::assertSame($duration_field, $fields->getTimeframeFields()->getEndPeriodField());
    }

    public function testItThrowsWhenTrackerHasNoArtifactLinkField(): void
    {
        $tracker = $this->buildTestTracker(27);
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')->andReturn([]);

        $this->expectException(NoArtifactLinkFieldException::class);
        $this->gatherer->gather($tracker);
    }

    public function testItThrowsWhenTrackerHasNoTitleField(): void
    {
        $tracker        = $this->buildTestTracker(27);
        $this->mockArtifactLinkField($tracker);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturnNull();
        $this->title_factory->shouldReceive('getByTracker')->andReturn($title_semantic);

        $this->expectException(NoTitleFieldException::class);
        $this->gatherer->gather($tracker);
    }

    public function testItThrowsWhenTrackerHasATitleFieldWithIncorrectType(): void
    {
        $tracker        = $this->buildTestTracker(27);
        $this->mockArtifactLinkField($tracker);

        $title_field    = M::mock(\Tracker_FormElement_Field_Text::class);
        $title_field->shouldReceive('getId')->andReturn(10);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturn($title_field);
        $this->title_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($title_semantic);

        $this->expectException(TitleFieldHasIncorrectTypeException::class);
        $this->gatherer->gather($tracker);
    }

    public function testItThrowsWhenTrackerHasNoDescriptionField(): void
    {
        $tracker        = $this->buildTestTracker(27);
        $this->mockArtifactLinkField($tracker);
        $this->mockTitleField($tracker);
        $description_semantic = M::mock(\Tracker_Semantic_Description::class);
        $description_semantic->shouldReceive('getField')->andReturnNull();
        $this->description_factory->shouldReceive('getByTracker')->andReturn($description_semantic);

        $this->expectException(NoDescriptionFieldException::class);
        $this->gatherer->gather($tracker);
    }

    public function testItThrowsWhenTrackerHasNoStatusField(): void
    {
        $tracker        = $this->buildTestTracker(27);
        $this->mockArtifactLinkField($tracker);
        $this->mockTitleField($tracker);
        $this->mockDescriptionField($tracker);
        $status_semantic = M::mock(\Tracker_Semantic_Status::class);
        $status_semantic->shouldReceive('getField')->andReturnNull();
        $this->status_factory->shouldReceive('getByTracker')->andReturn($status_semantic);

        $this->expectException(NoStatusFieldException::class);
        $this->gatherer->gather($tracker);
    }

    public function testItThrowsWhenTrackerHasNoStartDateField(): void
    {
        $tracker        = $this->buildTestTracker(27);
        $this->mockArtifactLinkField($tracker);
        $this->mockTitleField($tracker);
        $this->mockDescriptionField($tracker);
        $this->mockStatusField($tracker);
        $time_frame_semantic = new SemanticTimeframe($tracker, null, null, null);
        $this->time_frame_builder->shouldReceive('getSemantic')->andReturn($time_frame_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->gatherer->gather($tracker);
    }

    public function testItThrowsWhenTrackerHasNeitherEndDateNorDuration(): void
    {
        $tracker        = $this->buildTestTracker(27);
        $this->mockArtifactLinkField($tracker);
        $this->mockTitleField($tracker);
        $this->mockDescriptionField($tracker);
        $this->mockStatusField($tracker);
        $start_date_field    = M::mock(\Tracker_FormElement_Field_Date::class);
        $time_frame_semantic = new SemanticTimeframe($tracker, $start_date_field, null, null);
        $this->time_frame_builder->shouldReceive('getSemantic')->andReturn($time_frame_semantic);

        $this->expectException(MissingTimeFrameFieldException::class);
        $this->gatherer->gather($tracker);
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
            \Tuleap\Tracker\TrackerColor::default(),
            false
        );
    }

    private function mockArtifactLinkField(\Tracker $tracker): \Tracker_FormElement_Field_ArtifactLink
    {
        $artifact_link_field = M::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->form_element_factory->shouldReceive('getUsedArtifactLinkFields')
            ->once()
            ->with($tracker)
            ->andReturn([$artifact_link_field]);
        return $artifact_link_field;
    }

    private function mockTitleField(\Tracker $tracker): \Tracker_FormElement_Field_Text
    {
        $title_field    = M::mock(\Tracker_FormElement_Field_String::class);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturn($title_field);
        $this->title_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($title_semantic);
        return $title_field;
    }

    private function mockDescriptionField(\Tracker $tracker): \Tracker_FormElement_Field_Text
    {
        $description_field = M::mock(\Tracker_FormElement_Field_Text::class);
        $description_semantic = M::mock(\Tracker_Semantic_Description::class);
        $description_semantic->shouldReceive('getField')->andReturn($description_field);
        $this->description_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($description_semantic);
        return $description_field;
    }

    private function mockStatusField(\Tracker $tracker): \Tracker_FormElement_Field_List
    {
        $status_field = M::mock(\Tracker_FormElement_Field_List::class);
        $status_semantic = M::mock(\Tracker_Semantic_Status::class);
        $status_semantic->shouldReceive('getField')->andReturn($status_field);
        $this->status_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($status_semantic);
        return $status_field;
    }

    /**
     * @return \Tracker_FormElement_Field[]
     */
    private function mockTimeFrameFields(\Tracker $tracker): array
    {
        $start_date_field = M::mock(\Tracker_FormElement_Field_Date::class);
        $duration_field = M::mock(\Tracker_FormElement_Field_Numeric::class);
        $time_frame_semantic = new SemanticTimeframe($tracker, $start_date_field, $duration_field, null);
        $this->time_frame_builder->shouldReceive('getSemantic')
            ->once()
            ->with($tracker)
            ->andReturn($time_frame_semantic);
        return [$start_date_field, $duration_field];
    }
}
