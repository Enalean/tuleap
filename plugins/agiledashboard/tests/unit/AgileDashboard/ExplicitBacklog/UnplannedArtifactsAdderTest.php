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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class UnplannedArtifactsAdderTest extends TestCase
{
    private UnplannedArtifactsAdder $adder;
    private ArtifactsInExplicitBacklogDao&MockObject $artifacts_in_explicit_backlog_dao;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private PlannedArtifactDao&MockObject $planned_artifact_dao;
    private Artifact $artifact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao              = $this->createMock(PlannedArtifactDao::class);
        $this->explicit_backlog_dao              = $this->createMock(ExplicitBacklogDao::class);

        $this->adder = new UnplannedArtifactsAdder(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->planned_artifact_dao
        );

        $tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->build();

        $this->artifact = ArtifactTestBuilder::anArtifact(1)
            ->inTracker($tracker)
            ->build();
    }

    public function testItDoesNotAddArtifactIfProjectDoesNotUsesExplicitBacklog(): void
    {
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');

        $this->adder->addArtifactToTopBacklog($this->artifact);
    }

    public function testItDoesNotAddArtifactIfArtifactAlreadyPlanned(): void
    {
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->planned_artifact_dao->method('isArtifactPlannedInAMilestoneOfTheProject')
            ->with(1, 101)
            ->willReturn(true);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');

        self::expectException(ArtifactAlreadyPlannedException::class);

        $this->adder->addArtifactToTopBacklog($this->artifact);
    }

    public function testItAddsArtifactInTopBacklog(): void
    {
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->planned_artifact_dao->method('isArtifactPlannedInAMilestoneOfTheProject')
            ->with(1, 101)
            ->willReturn(false);

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('addArtifactToProjectBacklog');

        $this->adder->addArtifactToTopBacklog($this->artifact);
    }

    public function testItAddsArtifactInTopBacklogFromIds(): void
    {
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->planned_artifact_dao->method('isArtifactPlannedInAMilestoneOfTheProject')
            ->with(1, 101)
            ->willReturn(false);

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('addArtifactToProjectBacklog');

        $this->adder->addArtifactToTopBacklogFromIds(1, 101);
    }
}
