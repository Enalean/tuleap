<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Planning;
use Planning_NoMilestone;
use Project;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NoMilestoneTest extends TestCase
{
    private Project $project;
    private Planning $planning;
    private Planning_NoMilestone $milestone;

    #[\Override]
    protected function setUp(): void
    {
        $this->project   = ProjectTestBuilder::aProject()->withId(123)->build();
        $this->planning  = PlanningBuilder::aPlanning(123)->withId(9999)->build();
        $this->milestone = new Planning_NoMilestone($this->project, $this->planning);
    }

    public function testItHasAPlanning(): void
    {
        self::assertSame($this->planning, $this->milestone->getPlanning());
        self::assertSame($this->planning->getId(), $this->milestone->getPlanningId());
    }

    public function testItHasAProject(): void
    {
        self::assertSame($this->project, $this->milestone->getProject());
        self::assertSame($this->project->getID(), $this->milestone->getGroupId());
    }

    public function testItMayBeNull(): void
    {
        self::assertNull($this->milestone->getArtifact());
        self::assertNull($this->milestone->getArtifactId());
        self::assertNull($this->milestone->getArtifactTitle());
        self::assertTrue(
            $this->milestone->userCanView(UserTestBuilder::buildWithDefaults()),
            'any user should be able to read an empty milstone'
        );
    }
}
