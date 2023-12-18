<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use Planning;
use Planning_ArtifactMilestone;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class PromotedMilestoneTest extends TestCase
{
    public function testItAddsAPromotedSprint(): void
    {
        $project  = ProjectTestBuilder::aProject()->withId(101)->build();
        $release1 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(1)->build(),
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );
        $sprint1  = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(10)->build(),
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );

        $promoted_milestone = new PromotedMilestone($release1);
        $promoted_milestone->addPromotedSubMilestone(Option::fromValue($sprint1));

        $this->assertEquals([$sprint1], $promoted_milestone->getSubMilestoneList());
    }
}
