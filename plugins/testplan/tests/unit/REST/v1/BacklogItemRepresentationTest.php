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

namespace Tuleap\TestPlan\REST\v1;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_FormElement_Field_ArtifactLink;

class BacklogItemRepresentationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCannotAddATestIfThereIsNoArtifactLinkField()
    {
        $artifact = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker  = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getName')->andReturn('tracker_name');
        $tracker->shouldReceive('getProject')->andReturn(Mockery::spy(\Project::class));
        $artifact->shouldReceive(
            [
                'getAnArtifactLinkField' => null,
                'getTracker'             => $tracker,
            ]
        );

        $backlog_item = Mockery::spy(
            \AgileDashboard_Milestone_Backlog_IBacklogItem::class
        );
        $backlog_item->shouldReceive('getArtifact')->andReturn($artifact);
        $user = Mockery::mock(PFUser::class);

        $representation = new BacklogItemRepresentation($backlog_item, $user);

        $this->assertFalse($representation->can_add_a_test);
    }

    public function testItCannotAddATestIfThereIsAnArtifactLinkFieldButItCannotBeUpdated()
    {
        $field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('userCanUpdate')->andReturnFalse();

        $artifact = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker  = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getName')->andReturn('tracker_name');
        $tracker->shouldReceive('getProject')->andReturn(Mockery::spy(\Project::class));
        $artifact->shouldReceive(
            [
                'getAnArtifactLinkField' => null,
                'getTracker'             => $tracker,
            ]
        );

        $backlog_item = Mockery::spy(
            \AgileDashboard_Milestone_Backlog_IBacklogItem::class
        );
        $backlog_item->shouldReceive('getArtifact')->andReturn($artifact);
        $user = Mockery::mock(PFUser::class);

        $representation = new BacklogItemRepresentation($backlog_item, $user);

        $this->assertFalse($representation->can_add_a_test);
    }

    public function testItCanAddATestIfThereIsAnArtifactLinkFieldAndItCanBeUpdated()
    {
        $field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $field->shouldReceive('userCanUpdate')->andReturnTrue();

        $artifact = Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class);
        $tracker  = Mockery::spy(Tracker::class);
        $tracker->shouldReceive('getName')->andReturn('tracker_name');
        $tracker->shouldReceive('getProject')->andReturn(Mockery::spy(\Project::class));
        $artifact->shouldReceive(
            [
                'getAnArtifactLinkField' => null,
                'getTracker'             => $tracker,
            ]
        );

        $backlog_item = Mockery::spy(
            \AgileDashboard_Milestone_Backlog_IBacklogItem::class
        );
        $backlog_item->shouldReceive('getArtifact')->andReturn($artifact);
        $user = Mockery::mock(PFUser::class);

        $representation = new BacklogItemRepresentation($backlog_item, $user);

        $this->assertFalse($representation->can_add_a_test);
    }
}
