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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

final class TimeframeIsAlignedVerifierTest extends TestCase
{
    private const FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID  = 1024;
    private const SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID = 2048;

    private TimeframeIsAlignedVerifier $verifier;
    private SemanticTimeframeDao&\PHPUnit\Framework\MockObject\MockObject $dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao      = $this->createMock(SemanticTimeframeDao::class);
        $this->verifier = new TimeframeIsAlignedVerifier($this->dao);
    }

    public function testItReturnsFalseIfAtLeastOneTrackerDoesNotHaveTheSemanticTimeframeDefined(): void
    {
        $tracker_reference = TrackerReferenceStub::withDefaults();
        $source_trackers   = $this->buildSourceTrackerCollection($tracker_reference);

        $configuration_errors = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);

        $this->dao->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')->willReturn(1);

        self::assertFalse($this->verifier->isTimeframeWellConfigured($tracker_reference, $source_trackers, $configuration_errors));
    }

    public function testItReturnsFalseIfSemanticTimeframesDoesNotUsesTheSameFieldTypes(): void
    {
        $tracker_reference = TrackerReferenceStub::withDefaults();
        $source_trackers   = $this->buildSourceTrackerCollection($tracker_reference);

        $configuration_errors = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);

        $this->dao->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')->willReturn(0);
        $this->dao->method('areTimeFrameSemanticsUsingSameTypeOfField')->willReturn(false);

        self::assertFalse($this->verifier->isTimeframeWellConfigured($tracker_reference, $source_trackers, $configuration_errors));
    }

    public function testItReturnsTrueIfSemanticTimeframesAreWellConfigured(): void
    {
        $tracker_reference = TrackerReferenceStub::withDefaults();
        $source_trackers   = $this->buildSourceTrackerCollection($tracker_reference);

        $configuration_errors = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);

        $this->dao->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')->willReturn(0);
        $this->dao->method('areTimeFrameSemanticsUsingSameTypeOfField')->willReturn(true);

        self::assertTrue($this->verifier->isTimeframeWellConfigured($tracker_reference, $source_trackers, $configuration_errors));
    }

    private function buildSourceTrackerCollection(TrackerReferenceStub $tracker_reference): SourceTrackerCollection
    {
        $user_identifier = UserIdentifierStub::buildGenericUser();

        $teams = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(101),
            ProjectReferenceStub::withId(102),
        );

        $retriever = RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID),
            TrackerReferenceStub::withId(self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID)
        );

        $trackers = TrackerCollection::buildRootPlanningMilestoneTrackers(
            $retriever,
            $teams,
            $user_identifier,
            new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
        );

        return SourceTrackerCollection::fromProgramAndTeamTrackers(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($tracker_reference),
            ProgramIdentifierBuilder::build(),
            $trackers,
            $user_identifier,
        );
    }
}
