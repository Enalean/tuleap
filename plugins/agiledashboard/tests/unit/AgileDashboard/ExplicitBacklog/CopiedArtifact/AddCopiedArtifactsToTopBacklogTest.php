<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog\CopiedArtifact;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

#[DisableReturnValueGenerationForTestDoubles]
final class AddCopiedArtifactsToTopBacklogTest extends TestCase
{
    private const SOURCE_ARTIFACT_ID = 9930;
    private const COPIED_ARTIFACT_ID = 9945;

    private const SOURCE_CHILD_ARTIFACT_ID = 9931;
    private const COPIED_CHILD_ARTIFACT_ID = 9946;

    private ExplicitBacklogDao&MockObject $explicit_backlog_dao;
    private ArtifactsInExplicitBacklogDao&MockObject $artifacts_in_explicit_backlog_dao;
    private PlannedArtifactDao&MockObject $planned_artifact_dao;
    private PlanningDao&MockObject $planning_dao;

    protected function setUp(): void
    {
        $this->explicit_backlog_dao              = $this->createMock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao              = $this->createMock(PlannedArtifactDao::class);
        $this->planning_dao                      = $this->createMock(PlanningDao::class);
    }

    private function buildAddCopiedArtifactsToTopBacklog(RetrieveArtifact $artifact_retriever): AddCopiedArtifactsToTopBacklog
    {
        return new AddCopiedArtifactsToTopBacklog(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->planned_artifact_dao,
            $artifact_retriever,
            $this->planning_dao,
        );
    }

    public function testItDoesNothingIfProjectDoesNotUseExplicitBacklog(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectDoesNotUseExplicitBacklog();

        $this->artifacts_in_explicit_backlog_dao->expects($this->never())->method('addArtifactToProjectBacklog');

        $adder = $this->buildAddCopiedArtifactsToTopBacklog(RetrieveArtifactStub::withNoArtifact());
        $adder->addCopiedArtifactsToTopBacklog(
            new Tracker_XML_Importer_ArtifactImportedMapping(),
            $project,
        );
    }

    public function testItDoesNothingIfCopiedArtifactsAreNotExisting(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();

        $this->artifacts_in_explicit_backlog_dao->expects($this->never())->method('addArtifactToProjectBacklog');

        $adder = $this->buildAddCopiedArtifactsToTopBacklog(RetrieveArtifactStub::withNoArtifact());
        $adder->addCopiedArtifactsToTopBacklog(
            $this->buildMappingWithOneLevelContent(),
            $project,
        );
    }

    public function testItDoesNothingIfCopiedArtifactsTrackersAreNotInPlanning(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();
        $this->planning_dao->method('searchBacklogTrackersByTrackerId')->willReturn([]);

        $this->artifacts_in_explicit_backlog_dao->expects($this->never())->method('addArtifactToProjectBacklog');

        $adder = $this->buildAddCopiedArtifactsToTopBacklog(
            RetrieveArtifactStub::withArtifacts(ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build()),
        );
        $adder->addCopiedArtifactsToTopBacklog(
            $this->buildMappingWithOneLevelContent(),
            $project,
        );
    }

    public function testItDoesNothingIfCopiedArtifactsAreNotPartOfBacklogOrPlanned(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();
        $this->planning_dao->method('searchBacklogTrackersByTrackerId')->willReturn([['planning_id' => 1, 'tracker_id' => 34]]);
        $this->mockArtifactIsNotPlanned(self::SOURCE_ARTIFACT_ID);

        $this->artifacts_in_explicit_backlog_dao->expects($this->never())->method('addArtifactToProjectBacklog');

        $adder = $this->buildAddCopiedArtifactsToTopBacklog(
            RetrieveArtifactStub::withArtifacts(ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build()),
        );
        $adder->addCopiedArtifactsToTopBacklog(
            $this->buildMappingWithOneLevelContent(),
            $project,
        );
    }

    public function testItAddsCopiedArtifactInBacklogIfBaseArtifactIsInBacklog(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();
        $this->planning_dao->method('searchBacklogTrackersByTrackerId')->willReturn([['planning_id' => 1, 'tracker_id' => 34]]);
        $this->mockArtifactIsInBacklog(self::SOURCE_ARTIFACT_ID);

        $this->artifacts_in_explicit_backlog_dao->expects($this->once())->method('addArtifactToProjectBacklog');

        $adder = $this->buildAddCopiedArtifactsToTopBacklog(
            RetrieveArtifactStub::withArtifacts(ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build()),
        );
        $adder->addCopiedArtifactsToTopBacklog(
            $this->buildMappingWithOneLevelContent(),
            $project,
        );
    }

    public function testItAddsCopiedArtifactInBacklogIfBaseArtifactIsInMilestone(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();
        $this->planning_dao->method('searchBacklogTrackersByTrackerId')->willReturn([['planning_id' => 1, 'tracker_id' => 34]]);
        $this->mockArtifactIsInMilestone(self::SOURCE_ARTIFACT_ID);

        $this->artifacts_in_explicit_backlog_dao->expects($this->once())->method('addArtifactToProjectBacklog');

        $adder = $this->buildAddCopiedArtifactsToTopBacklog(
            RetrieveArtifactStub::withArtifacts(ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build()),
        );
        $adder->addCopiedArtifactsToTopBacklog(
            $this->buildMappingWithOneLevelContent(),
            $project,
        );
    }

    public function testItAddsCopiedArtifactsIfBaseAndChildrenArePartOfBacklogOrPlanned(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();
        $this->planning_dao->method('searchBacklogTrackersByTrackerId')->willReturn([['planning_id' => 1, 'tracker_id' => 34]]);
        $this->mockBothArtifactAndChildPlanned(
            self::SOURCE_ARTIFACT_ID,
            self::SOURCE_CHILD_ARTIFACT_ID,
        );

        $this->artifacts_in_explicit_backlog_dao->expects($this->exactly(2))->method('addArtifactToProjectBacklog');

        $adder = $this->buildAddCopiedArtifactsToTopBacklog(
            RetrieveArtifactStub::withArtifacts(
                ArtifactTestBuilder::anArtifact(self::SOURCE_ARTIFACT_ID)->build(),
                ArtifactTestBuilder::anArtifact(self::SOURCE_CHILD_ARTIFACT_ID)->build(),
            ),
        );
        $adder->addCopiedArtifactsToTopBacklog(
            $this->buildMappingWithTwoLevelsContent(),
            $project,
        );
    }

    private function buildMappingWithOneLevelContent(): Tracker_XML_Importer_ArtifactImportedMapping
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(self::SOURCE_ARTIFACT_ID, self::COPIED_ARTIFACT_ID);

        return $mapping;
    }

    private function buildMappingWithTwoLevelsContent(): Tracker_XML_Importer_ArtifactImportedMapping
    {
        $mapping = new Tracker_XML_Importer_ArtifactImportedMapping();
        $mapping->add(self::SOURCE_ARTIFACT_ID, self::COPIED_ARTIFACT_ID);
        $mapping->add(self::SOURCE_CHILD_ARTIFACT_ID, self::COPIED_CHILD_ARTIFACT_ID);

        return $mapping;
    }

    private function mockProjectDoesNotUseExplicitBacklog(): void
    {
        $this->explicit_backlog_dao
            ->expects($this->once())
            ->method('isProjectUsingExplicitBacklog')
            ->willReturn(false);
    }

    private function mockProjectUsesExplicitBacklog(): void
    {
        $this->explicit_backlog_dao
            ->expects($this->once())
            ->method('isProjectUsingExplicitBacklog')
            ->willReturn(true);
    }

    private function mockArtifactIsNotPlanned(int $artifact_id): void
    {
        $this->artifacts_in_explicit_backlog_dao
            ->expects($this->once())
            ->method('isArtifactInTopBacklogOfProject')
            ->with($artifact_id, 101)
            ->willReturn(false);

        $this->planned_artifact_dao
            ->expects($this->once())
            ->method('isArtifactPlannedInAMilestoneOfTheProject')
            ->with($artifact_id, 101)
            ->willReturn(false);
    }

    private function mockArtifactIsInBacklog(int $artifact_id): void
    {
        $this->artifacts_in_explicit_backlog_dao
            ->expects($this->once())
            ->method('isArtifactInTopBacklogOfProject')
            ->with($artifact_id, 101)
            ->willReturn(true);
    }

    private function mockArtifactIsInMilestone(int $artifact_id): void
    {
        $this->artifacts_in_explicit_backlog_dao
            ->expects($this->once())
            ->method('isArtifactInTopBacklogOfProject')
            ->with($artifact_id, 101)
            ->willReturn(false);

        $this->planned_artifact_dao
            ->expects($this->once())
            ->method('isArtifactPlannedInAMilestoneOfTheProject')
            ->with($artifact_id, 101)
            ->willReturn(true);
    }

    private function mockBothArtifactAndChildPlanned(int $base_artifact_id, int $child_artifact_id): void
    {
        $this->artifacts_in_explicit_backlog_dao
            ->method('isArtifactInTopBacklogOfProject')
            ->willReturnMap([
                [$base_artifact_id, 101, true],
                [$child_artifact_id, 101, false],
            ]);

        $this->planned_artifact_dao
            ->method('isArtifactPlannedInAMilestoneOfTheProject')
            ->willReturnMap([
                [$base_artifact_id, 101, false],
                [$child_artifact_id, 101, true],
            ]);
    }
}
