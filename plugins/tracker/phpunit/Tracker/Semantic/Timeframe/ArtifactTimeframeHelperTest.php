<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\Semantic\Timeframe;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ArtifactTimeframeHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItShouldReturnFalseIfSemanticIsNotDefined(): void
    {
        $semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);
        $timeframe_builder          = Mockery::mock(TimeframeBuilder::class);
        $user                       = Mockery::mock(\PFUser::class);
        $duration_field             = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);
        $tracker                    = Mockery::mock(\Tracker::class);
        $semantic                   = Mockery::mock(SemanticTimeframe::class);

        $duration_field->shouldReceive('getTracker')->andReturn($tracker);
        $semantic_timeframe_builder->shouldReceive('getSemantic')->with($tracker)->andReturn($semantic);
        $semantic->shouldReceive('isDefined')->andReturnFalse();

        $artifact_timeframe_helper  = new ArtifactTimeframeHelper($semantic_timeframe_builder, $timeframe_builder);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field));
    }

    public function testItShouldReturnFalseIfNotUsedInSemantics(): void
    {
        $semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);
        $timeframe_builder          = Mockery::mock(TimeframeBuilder::class);
        $user                       = Mockery::mock(\PFUser::class);
        $duration_field             = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);
        $tracker                    = Mockery::mock(\Tracker::class);
        $semantic                   = Mockery::mock(SemanticTimeframe::class);
        $start_date_field           = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $duration_field->shouldReceive('getTracker')->andReturn($tracker);
        $semantic_timeframe_builder->shouldReceive('getSemantic')->with($tracker)->andReturn($semantic);
        $semantic->shouldReceive('isDefined')->andReturnTrue();
        $semantic->shouldReceive('isDurationField')->andReturnFalse();
        $semantic->shouldReceive('isEndDateField')->andReturnFalse();
        $semantic->shouldReceive('getStartDateField')->andReturn($start_date_field);
        $start_date_field->shouldReceive('userCanRead')->with($user)->andReturnFalse();

        $artifact_timeframe_helper  = new ArtifactTimeframeHelper($semantic_timeframe_builder, $timeframe_builder);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field));
    }

    public function testItShouldReturnFalseIfUserCannotViewStartDate(): void
    {
        $semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);
        $timeframe_builder          = Mockery::mock(TimeframeBuilder::class);
        $user                       = Mockery::mock(\PFUser::class);
        $duration_field             = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);
        $tracker                    = Mockery::mock(\Tracker::class);
        $semantic                   = Mockery::mock(SemanticTimeframe::class);
        $start_date_field           = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $duration_field->shouldReceive('getTracker')->andReturn($tracker);
        $semantic_timeframe_builder->shouldReceive('getSemantic')->with($tracker)->andReturn($semantic);
        $semantic->shouldReceive('isDefined')->andReturnTrue();
        $semantic->shouldReceive('isDurationField')->andReturnTrue();
        $semantic->shouldReceive('isEndDateField')->andReturnFalse();
        $semantic->shouldReceive('getStartDateField')->andReturn($start_date_field);
        $start_date_field->shouldReceive('userCanRead')->with($user)->andReturnFalse();

        $artifact_timeframe_helper  = new ArtifactTimeframeHelper($semantic_timeframe_builder, $timeframe_builder);

        $this->assertFalse($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field));
    }

    public function testItShouldReturnTrueIfUserShouldBeShownArtifactHelperForDuration(): void
    {
        $semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);
        $timeframe_builder          = Mockery::mock(TimeframeBuilder::class);
        $user                       = Mockery::mock(\PFUser::class);
        $duration_field             = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);
        $tracker                    = Mockery::mock(\Tracker::class);
        $semantic                   = Mockery::mock(SemanticTimeframe::class);
        $start_date_field           = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $duration_field->shouldReceive('getTracker')->andReturn($tracker);
        $semantic_timeframe_builder->shouldReceive('getSemantic')->with($tracker)->andReturn($semantic);
        $semantic->shouldReceive('isDefined')->andReturnTrue();
        $semantic->shouldReceive('isDurationField')->andReturnTrue();
        $semantic->shouldReceive('isEndDateField')->andReturnFalse();
        $semantic->shouldReceive('getStartDateField')->andReturn($start_date_field);
        $start_date_field->shouldReceive('userCanRead')->with($user)->andReturnTrue();

        $artifact_timeframe_helper  = new ArtifactTimeframeHelper($semantic_timeframe_builder, $timeframe_builder);

        $this->assertTrue($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field));
    }

    public function testItShouldReturnTrueIfUserShouldBeShownArtifactHelperForEndDate(): void
    {
        $semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);
        $timeframe_builder          = Mockery::mock(TimeframeBuilder::class);
        $user                       = Mockery::mock(\PFUser::class);
        $duration_field             = Mockery::mock(\Tracker_FormElement_Field_Numeric::class);
        $tracker                    = Mockery::mock(\Tracker::class);
        $semantic                   = Mockery::mock(SemanticTimeframe::class);
        $start_date_field           = Mockery::mock(\Tracker_FormElement_Field_Date::class);

        $duration_field->shouldReceive('getTracker')->andReturn($tracker);
        $semantic_timeframe_builder->shouldReceive('getSemantic')->with($tracker)->andReturn($semantic);
        $semantic->shouldReceive('isDefined')->andReturnTrue();
        $semantic->shouldReceive('isDurationField')->andReturnFalse();
        $semantic->shouldReceive('isEndDateField')->andReturnTrue();
        $semantic->shouldReceive('getStartDateField')->andReturn($start_date_field);
        $start_date_field->shouldReceive('userCanRead')->with($user)->andReturnTrue();

        $artifact_timeframe_helper  = new ArtifactTimeframeHelper($semantic_timeframe_builder, $timeframe_builder);

        $this->assertTrue($artifact_timeframe_helper->artifactHelpShouldBeShownToUser($user, $duration_field));
    }
}
