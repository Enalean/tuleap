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
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var SynchronizedFieldCollectionBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->form_element_factory = M::mock(\Tracker_FormElementFactory::class);
        $this->builder              = new SynchronizedFieldCollectionBuilder($this->form_element_factory);
    }

    public function testBuildFromMilestoneTrackersReturnsACollection(): void
    {
        $first_tracker              = $this->buildTestTracker(103);
        $second_tracker             = $this->buildTestTracker(104);
        $milestones                 = new MilestoneTrackerCollection([$first_tracker, $second_tracker]);
        $user                       = UserTestBuilder::aUser()->build();
        $this->mockArtifactLinkField($first_tracker, $user);
        $this->mockArtifactLinkField($second_tracker, $user);

        $this->assertNotNull($this->builder->buildFromMilestoneTrackers($milestones, $user));
    }

    public function testBuildFromMilestoneTrackersReturnsNullWhenOneTrackerDoesNotHaveAnArtifactLinkField(): void
    {
        $first_tracker             = $this->buildTestTracker(103);
        $second_tracker            = $this->buildTestTracker(104);
        $milestones                = new MilestoneTrackerCollection([$first_tracker, $second_tracker]);
        $user                      = UserTestBuilder::aUser()->build();
        $this->mockArtifactLinkField($first_tracker, $user);
        $this->form_element_factory->shouldReceive('getAnArtifactLinkField')
            ->once()
            ->with($user, $second_tracker)
            ->andReturnNull();

        $this->expectException(NoArtifactLinkFieldException::class);
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
}
