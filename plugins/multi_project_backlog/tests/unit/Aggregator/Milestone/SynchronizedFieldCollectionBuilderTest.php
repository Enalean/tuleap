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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\Builders\UserTestBuilder;
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

    protected function setUp(): void
    {
        $this->form_element_factory = M::mock(\Tracker_FormElementFactory::class);
        $this->title_factory        = M::mock(\Tracker_Semantic_TitleFactory::class);
        $this->description_factory  = M::mock(\Tracker_Semantic_DescriptionFactory::class);
        $this->builder              = new SynchronizedFieldCollectionBuilder(
            $this->form_element_factory,
            $this->title_factory,
            $this->description_factory
        );
    }

    public function testBuildFromMilestoneTrackersReturnsACollection(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new MilestoneTrackerCollection([$first_tracker, $second_tracker]);
        $user           = UserTestBuilder::aUser()->build();
        $this->mockArtifactLinkField($first_tracker, $user);
        $this->mockArtifactLinkField($second_tracker, $user);
        $this->mockTitleField($first_tracker);
        $this->mockTitleField($second_tracker);
        $this->mockDescriptionField($first_tracker);
        $this->mockDescriptionField($second_tracker);

        $this->assertNotNull($this->builder->buildFromMilestoneTrackers($milestones, $user));
    }

    public function testItThrowsWhenOneTrackerDoesNotHaveAnArtifactLinkField(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new MilestoneTrackerCollection([$first_tracker, $second_tracker]);
        $user           = UserTestBuilder::aUser()->build();
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->with($user, $first_tracker)
            ->andReturnNull();

        $this->expectException(NoArtifactLinkFieldException::class);
        $this->builder->buildFromMilestoneTrackers($milestones, $user);
    }

    public function testItThrowsWhenOneTrackerDoesNotHaveATitleField(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new MilestoneTrackerCollection([$first_tracker, $second_tracker]);
        $user           = UserTestBuilder::aUser()->build();
        $this->mockArtifactLinkField($first_tracker, $user);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturnNull();
        $this->title_factory->shouldReceive('getByTracker')
            ->once()
            ->with($first_tracker)
            ->andReturn($title_semantic);

        $this->expectException(NoTitleFieldException::class);
        $this->builder->buildFromMilestoneTrackers($milestones, $user);
    }

    public function testItThrowsWhenOneTrackerDoesNotHaveADescriptionField(): void
    {
        $first_tracker  = $this->buildTestTracker(103);
        $second_tracker = $this->buildTestTracker(104);
        $milestones     = new MilestoneTrackerCollection([$first_tracker, $second_tracker]);
        $user           = UserTestBuilder::aUser()->build();
        $this->mockArtifactLinkField($first_tracker, $user);
        $this->mockTitleField($first_tracker);
        $description_semantic = M::mock(\Tracker_Semantic_Description::class);
        $description_semantic->shouldReceive('getField')->andReturnNull();
        $this->description_factory->shouldReceive('getByTracker')
            ->once()
            ->with($first_tracker)
            ->andReturn($description_semantic);

        $this->expectException(NoDescriptionFieldException::class);
        $this->builder->buildFromMilestoneTrackers($milestones, $user);
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

    private function mockArtifactLinkField(\Tracker $tracker, \PFUser $user): void
    {
        $artifact_link_field = M::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->with($user, $tracker)
            ->andReturn($artifact_link_field);
    }

    private function mockTitleField(\Tracker $tracker): void
    {
        $title_field    = M::mock(\Tracker_FormElement_Field::class);
        $title_semantic = M::mock(\Tracker_Semantic_Title::class);
        $title_semantic->shouldReceive('getField')->andReturn($title_field);
        $this->title_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($title_semantic);
    }

    private function mockDescriptionField(\Tracker $tracker): void
    {
        $description_field    = M::mock(\Tracker_FormElement_Field::class);
        $description_semantic = M::mock(\Tracker_Semantic_Title::class);
        $description_semantic->shouldReceive('getField')->andReturn($description_field);
        $this->description_factory->shouldReceive('getByTracker')
            ->once()
            ->with($tracker)
            ->andReturn($description_semantic);
    }
}
