<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UnplannedReportCriterionMatchingIdsRetrieverTest extends TestCase
{
    private UnplannedReportCriterionMatchingIdsRetriever $retriever;
    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private ArtifactsInExplicitBacklogDao&MockObject $artifacts_in_explicit_backlog_dao;
    private PlannedArtifactDao&MockObject $planned_artifact_dao;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private Tracker $tracker;
    private PFUser $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao              = $this->createMock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao              = $this->createMock(PlannedArtifactDao::class);
        $this->artifact_factory                  = $this->createMock(Tracker_ArtifactFactory::class);

        $this->retriever = new UnplannedReportCriterionMatchingIdsRetriever(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->planned_artifact_dao,
            $this->artifact_factory
        );

        $this->user    = UserTestBuilder::buildWithDefaults();
        $project       = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withProject($project)
            ->build();
    }

    public function testItThrowsAnExceptionIfProjectIsNotInExplicitBacklog(): void
    {
        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(false);

        $this->expectException(ProjectNotUsingExplicitBacklogException::class);

        $this->retriever->getMatchingIds($this->tracker, $this->user);
    }

    public function testItReturnsTheMatchingIds(): void
    {
        $this->explicit_backlog_dao->expects($this->once())->method('isProjectUsingExplicitBacklog')
            ->with(101)
            ->willReturn(true);

        $this->mockDataReturnedByDaos();
        $this->mockArtifactFactory();

        $expected = [
            142 => true,
        ];

        self::assertSame(
            $expected,
            $this->retriever->getMatchingIds($this->tracker, $this->user)
        );
    }

    private function mockDataReturnedByDaos(): void
    {
        $this->artifacts_in_explicit_backlog_dao->expects($this->once())->method('getAllArtifactNotInTopBacklogInTracker')
            ->with(1)
            ->willReturn([
                ['artifact_id' => 142],
                ['artifact_id' => 143],
                ['artifact_id' => 148],
                ['artifact_id' => 152],
            ]);

        $this->planned_artifact_dao->expects($this->once())->method('gatAllPlannedArtifactsOfTheProject')
            ->with(101, 1)
            ->willReturn([
                ['artifact_id' => 143],
                ['artifact_id' => 152],
            ]);
    }

    private function mockArtifactFactory(): void
    {
        $matcher = $this->exactly(2);
        $this->artifact_factory->expects($matcher)->method('getArtifactByIdUserCanView')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->user, $parameters[0]);
                self::assertSame(142, $parameters[1]);
                return ArtifactTestBuilder::anArtifact(142)->build();
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->user, $parameters[0]);
                self::assertSame(148, $parameters[1]);
                return null;
            }
        });
    }
}
