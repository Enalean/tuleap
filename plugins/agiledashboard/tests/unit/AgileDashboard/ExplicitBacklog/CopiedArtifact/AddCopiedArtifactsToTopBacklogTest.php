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

use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddCopiedArtifactsToTopBacklogTest extends TestCase
{
    private const SOURCE_ARTIFACT_ID = 9930;
    private const COPIED_ARTIFACT_ID = 9945;

    private const SOURCE_CHILD_ARTIFACT_ID = 9931;
    private const COPIED_CHILD_ARTIFACT_ID = 9946;

    private AddCopiedArtifactsToTopBacklog $adder;
    /**
     * @var ExplicitBacklogDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $explicit_backlog_dao;
    /**
     * @var ArtifactsInExplicitBacklogDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $artifacts_in_explicit_backlog_dao;
    /**
     * @var PlannedArtifactDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $planned_artifact_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao              = $this->createMock(ExplicitBacklogDao::class);
        $this->artifacts_in_explicit_backlog_dao = $this->createMock(ArtifactsInExplicitBacklogDao::class);
        $this->planned_artifact_dao              = $this->createMock(PlannedArtifactDao::class);

        $this->adder = new AddCopiedArtifactsToTopBacklog(
            $this->explicit_backlog_dao,
            $this->artifacts_in_explicit_backlog_dao,
            $this->planned_artifact_dao,
        );
    }

    public function testItDoesNothingIfProjectDoesNotUseExplicitBacklog(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectDoesNotUseExplicitBacklog();

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');

        $this->adder->addCopiedArtifactsToTopBacklog(
            new Tracker_XML_Importer_ArtifactImportedMapping(),
            $project,
        );
    }

    public function testItDoesNothingIfCopiedArtifactsAreNotPartOfBacklogOrPlanned(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();
        $this->mockArtifactIsNotPlanned(self::SOURCE_ARTIFACT_ID);

        $this->artifacts_in_explicit_backlog_dao->expects(self::never())->method('addArtifactToProjectBacklog');

        $this->adder->addCopiedArtifactsToTopBacklog(
            $this->buildMappingWithOneLevelContent(),
            $project,
        );
    }

    public function testItAddsCopiedArtifactInBacklogIfBaseArtifactIsInBacklog(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();
        $this->mockArtifactIsInBacklog(self::SOURCE_ARTIFACT_ID);

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('addArtifactToProjectBacklog');

        $this->adder->addCopiedArtifactsToTopBacklog(
            $this->buildMappingWithOneLevelContent(),
            $project,
        );
    }

    public function testItAddsCopiedArtifactInBacklogIfBaseArtifactIsInMilestone(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();
        $this->mockArtifactIsInMilestone(self::SOURCE_ARTIFACT_ID);

        $this->artifacts_in_explicit_backlog_dao->expects(self::once())->method('addArtifactToProjectBacklog');

        $this->adder->addCopiedArtifactsToTopBacklog(
            $this->buildMappingWithOneLevelContent(),
            $project,
        );
    }

    public function testItAddsCopiedArtifactsIfBaseAndChildrenArePartOfBacklogOrPlanned(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $this->mockProjectUsesExplicitBacklog();
        $this->mockBothArtifactAndChildPlanned(
            self::SOURCE_ARTIFACT_ID,
            self::SOURCE_CHILD_ARTIFACT_ID,
        );

        $this->artifacts_in_explicit_backlog_dao->expects(self::exactly(2))->method('addArtifactToProjectBacklog');

        $this->adder->addCopiedArtifactsToTopBacklog(
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
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->willReturn(false);
    }

    private function mockProjectUsesExplicitBacklog(): void
    {
        $this->explicit_backlog_dao
            ->expects(self::once())
            ->method('isProjectUsingExplicitBacklog')
            ->willReturn(true);
    }

    private function mockArtifactIsNotPlanned(int $artifact_id): void
    {
        $this->artifacts_in_explicit_backlog_dao
            ->expects(self::once())
            ->method('isArtifactInTopBacklogOfProject')
            ->with($artifact_id, 101)
            ->willReturn(false);

        $this->planned_artifact_dao
            ->expects(self::once())
            ->method('isArtifactPlannedInAMilestoneOfTheProject')
            ->with($artifact_id, 101)
            ->willReturn(false);
    }

    private function mockArtifactIsInBacklog(int $artifact_id): void
    {
        $this->artifacts_in_explicit_backlog_dao
            ->expects(self::once())
            ->method('isArtifactInTopBacklogOfProject')
            ->with($artifact_id, 101)
            ->willReturn(true);
    }

    private function mockArtifactIsInMilestone(int $artifact_id): void
    {
        $this->artifacts_in_explicit_backlog_dao
            ->expects(self::once())
            ->method('isArtifactInTopBacklogOfProject')
            ->with($artifact_id, 101)
            ->willReturn(false);

        $this->planned_artifact_dao
            ->expects(self::once())
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
