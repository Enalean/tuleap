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

use PFUser;
use Tuleap\AgileDashboard\Milestone\Backlog\IBacklogItem;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BacklogItemRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCannotAddATestIfThereIsNoArtifactLinkField(): void
    {
        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $project  = $this->createMock(\Project::class);

        $project->method('getID')->willReturn(101);
        $project->method('getPublicName')->willReturn('project01');
        $project->method('getIconUnicodeCodepoint')->willReturn('');

        $tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject($project)
            ->withName('tracker_name')
            ->build();

        $artifact->method('getAnArtifactLinkField')->willReturn(null);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn(1);

        $backlog_item = $this->createMock(
            IBacklogItem::class
        );
        $backlog_item->method('getArtifact')->willReturn($artifact);
        $backlog_item->method('id')->willReturn(1);
        $backlog_item->method('title')->willReturn('title');
        $backlog_item->method('getShortType')->willReturn('short_type');
        $backlog_item->method('color')->willReturn('color');

        $user = $this->createMock(PFUser::class);

        $representation = new BacklogItemRepresentation($backlog_item, $user);

        self::assertFalse($representation->can_add_a_test);
    }

    public function testItCannotAddATestIfThereIsAnArtifactLinkFieldButItCannotBeUpdated(): void
    {
        $field = $this->createMock(ArtifactLinkField::class);
        $field->method('userCanUpdate')->willReturn(false);

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $project  = $this->createMock(\Project::class);

        $project->method('getID')->willReturn(101);
        $project->method('getPublicName')->willReturn('project01');
        $project->method('getIconUnicodeCodepoint')->willReturn('');

        $tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject($project)
            ->withName('tracker_name')
            ->build();

        $artifact->method('getAnArtifactLinkField')->willReturn($field);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn(1);

        $backlog_item = $this->createMock(
            IBacklogItem::class
        );
        $backlog_item->method('getArtifact')->willReturn($artifact);
        $backlog_item->method('id')->willReturn(1);
        $backlog_item->method('title')->willReturn('title');
        $backlog_item->method('getShortType')->willReturn('short_type');
        $backlog_item->method('color')->willReturn('color');

        $user = $this->createMock(PFUser::class);

        $representation = new BacklogItemRepresentation($backlog_item, $user);

        self::assertFalse($representation->can_add_a_test);
    }

    public function testItCanAddATestIfThereIsAnArtifactLinkFieldAndItCanBeUpdated(): void
    {
        $field = $this->createMock(ArtifactLinkField::class);
        $field->method('userCanUpdate')->willReturn(true);

        $artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $project  = $this->createMock(\Project::class);

        $project->method('getID')->willReturn(101);
        $project->method('getPublicName')->willReturn('project01');
        $project->method('getIconUnicodeCodepoint')->willReturn('');

        $tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject($project)
            ->withName('tracker_name')
            ->build();

        $artifact->method('getAnArtifactLinkField')->willReturn($field);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn(1);

        $backlog_item = $this->createMock(
            IBacklogItem::class
        );
        $backlog_item->method('getArtifact')->willReturn($artifact);
        $backlog_item->method('id')->willReturn(1);
        $backlog_item->method('title')->willReturn('title');
        $backlog_item->method('getShortType')->willReturn('short_type');
        $backlog_item->method('color')->willReturn('color');

        $user = $this->createMock(PFUser::class);

        $representation = new BacklogItemRepresentation($backlog_item, $user);

        self::assertTrue($representation->can_add_a_test);
    }
}
