<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use AgileDashBoard_Semantic_InitialEffort;
use AgileDashboard_Semantic_InitialEffortFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetValue;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\AgileDashboard\Semantic\SemanticDoneFactory;

class BurnupCalculatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var BurnupCalculator
     */
    private $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $changeset_factory      = Mockery::mock(Tracker_Artifact_ChangesetFactory::class);
        $artifact_factory       = Mockery::mock(Tracker_ArtifactFactory::class);
        $burnup_dao             = Mockery::mock(BurnupDao::class);
        $initial_effort_factory = Mockery::mock(AgileDashboard_Semantic_InitialEffortFactory::class);
        $semantic_done_factory  = Mockery::mock(SemanticDoneFactory::class);

        $this->calculator = new BurnupCalculator(
            $changeset_factory,
            $artifact_factory,
            $burnup_dao,
            $initial_effort_factory,
            $semantic_done_factory
        );

        $burnup_dao->shouldReceive('searchLinkedArtifactsAtGivenTimestamp')
            ->with(101, 1537187828)
            ->andReturn([
                ['id' => 102],
                ['id' => 103]
            ]);

        $burnup_dao->shouldReceive('searchLinkedArtifactsAtGivenTimestamp')
            ->with(101, 1537189326)
            ->andReturn([
                ['id' => 102],
                ['id' => 103]
            ]);

        $tracker       = Mockery::mock(Tracker::class);
        $user_story_01 = Mockery::mock(Tracker_Artifact::class);
        $user_story_02 = Mockery::mock(Tracker_Artifact::class);

        $changeset_01 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset_01->shouldReceive('getArtifact')->andReturn($user_story_01);

        $changeset_02 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset_02->shouldReceive('getArtifact')->andReturn($user_story_01);

        $changeset_03 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset_03->shouldReceive('getArtifact')->andReturn($user_story_02);

        $changeset_04 = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset_04->shouldReceive('getArtifact')->andReturn($user_story_02);

        $user_story_01->shouldReceive('getTracker')->andReturn($tracker);
        $user_story_01->shouldReceive('isOpenAtGivenChangeset')->with($changeset_01)->andReturn(true);
        $user_story_01->shouldReceive('isOpenAtGivenChangeset')->with($changeset_02)->andReturn(false);

        $user_story_02->shouldReceive('getTracker')->andReturn($tracker);
        $user_story_02->shouldReceive('isOpenAtGivenChangeset')->with($changeset_03)->andReturn(true);
        $user_story_02->shouldReceive('isOpenAtGivenChangeset')->with($changeset_04)->andReturn(true);

        $artifact_factory->shouldReceive('getArtifactById')->with(102)->andReturn($user_story_01);
        $artifact_factory->shouldReceive('getArtifactById')->with(103)->andReturn($user_story_02);

        $semantic_initial_effort = Mockery::mock(AgileDashBoard_Semantic_InitialEffort::class);
        $initial_effort_field    = Mockery::mock(\Tracker_FormElement_Field::class);
        $semantic_initial_effort->shouldReceive('getField')->andReturn($initial_effort_field);

        $initial_effort_factory->shouldReceive('getByTracker')->with($tracker)->andReturn($semantic_initial_effort);

        $semantic_done = Mockery::mock(SemanticDone::class);
        $semantic_done_factory->shouldReceive('getInstanceByTracker')->with($tracker)->andReturn($semantic_done);

        $changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->with($user_story_01, 1537187828)
            ->andReturn($changeset_01);

        $changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->with($user_story_02, 1537187828)
            ->andReturn($changeset_03);

        $changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->with($user_story_01, 1537189326)
            ->andReturn($changeset_02);

        $changeset_factory->shouldReceive('getChangesetAtTimestamp')
            ->with($user_story_02, 1537189326)
            ->andReturn($changeset_04);

        $semantic_done->shouldReceive('isDone')->with($changeset_01)->andReturn(false);
        $semantic_done->shouldReceive('isDone')->with($changeset_02)->andReturn(true);
        $semantic_done->shouldReceive('isDone')->with($changeset_03)->andReturn(false);
        $semantic_done->shouldReceive('isDone')->with($changeset_04)->andReturn(false);

        $value_01 = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $value_01->shouldReceive('getValue')->andReturn(4);

        $value_02 = Mockery::mock(Tracker_Artifact_ChangesetValue::class);
        $value_02->shouldReceive('getValue')->andReturn(5);

        $user_story_01->shouldReceive('getValue')->with($initial_effort_field, $changeset_01)->andReturn($value_01);
        $user_story_01->shouldReceive('getValue')->with($initial_effort_field, $changeset_02)->andReturn($value_01);
        $user_story_02->shouldReceive('getValue')->with($initial_effort_field, $changeset_03)->andReturn($value_02);
        $user_story_02->shouldReceive('getValue')->with($initial_effort_field, $changeset_04)->andReturn($value_02);
    }

    public function testItCalculsBurnupWithFirstChangeset()
    {
        $effort = $this->calculator->getValue(101, 1537187828);

        $this->assertSame($effort->getTeamEffort(), 0.0);
        $this->assertSame($effort->getTotalEffort(), 9.0);
    }

    public function testItCalculsBurnupWithLastChangeset()
    {
        $effort = $this->calculator->getValue(101, 1537189326);

        $this->assertSame($effort->getTeamEffort(), 4.0);
        $this->assertSame($effort->getTotalEffort(), 9.0);
    }
}
