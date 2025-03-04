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

namespace Tuleap\TestManagement;

use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GetURIForMilestoneFromTTMTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID   = 1;
    private const PLANNING_ID  = 2;
    private const MILESTONE_ID = 3;

    private function getMilestone(): \Planning_Milestone
    {
        return new \Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build(),
            PlanningBuilder::aPlanning(self::PROJECT_ID)->withId(self::PLANNING_ID)->build(),
            ArtifactTestBuilder::anArtifact(self::MILESTONE_ID)->build(),
        );
    }

    public function testItReturnsTheDefaultURI(): void
    {
        $event = new GetURIForMilestoneFromTTM($this->getMilestone(), UserTestBuilder::buildWithDefaults());
        $this->assertEquals(
            '/plugins/agiledashboard/?pane=details&action=show&group_id=1&planning_id=2&aid=3',
            $event->getURI(),
        );
    }

    public function testItReturnsTheCustomURI(): void
    {
        $event = new GetURIForMilestoneFromTTM($this->getMilestone(), UserTestBuilder::buildWithDefaults());
        $event->setURI('/my/custom/uri');
        $this->assertEquals('/my/custom/uri', $event->getURI());
    }
}
