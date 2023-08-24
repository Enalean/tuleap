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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifyStatusIsAligned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\VerifyTimeframeIsAligned;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;

final class SemanticsVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID  = 1024;
    private const SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID = 2048;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_Semantic_TitleDao
     */
    private $title_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_Semantic_DescriptionDao
     */
    private $description_dao;
    private TrackerReference $program_increment_tracker;
    private TrackerCollection $trackers;
    private SourceTrackerCollection $source_trackers;

    protected function setUp(): void
    {
        $this->program_increment_tracker = TrackerReferenceStub::withDefaults();

        $user_identifier = UserIdentifierStub::buildGenericUser();
        $teams           = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(101),
            ProjectReferenceStub::withId(102),
        );

        $retriever             = RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
            TrackerReferenceStub::withId(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID),
            TrackerReferenceStub::withId(self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID)
        );
        $this->trackers        = TrackerCollection::buildRootPlanningMilestoneTrackers(
            $retriever,
            $teams,
            $user_identifier,
            new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
        );
        $this->source_trackers = SourceTrackerCollection::fromProgramAndTeamTrackers(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($this->program_increment_tracker),
            ProgramIdentifierBuilder::build(),
            $this->trackers,
            $user_identifier
        );

        $this->title_dao       = $this->createMock(\Tracker_Semantic_TitleDao::class);
        $this->description_dao = $this->createMock(\Tracker_Semantic_DescriptionDao::class);
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $configuration_errors = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);

        $this->title_dao->expects(self::once())
            ->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->with([1, self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID])
            ->willReturn(0);
        $this->description_dao->expects(self::once())
            ->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->with([1, self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, self::SECOND_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID])
            ->willReturn(0);

        $verifier = new SemanticsVerifier(
            $this->title_dao,
            $this->description_dao,
            new class implements VerifyStatusIsAligned {
                public function isStatusWellConfigured(
                    TrackerReference $tracker,
                    SourceTrackerCollection $source_tracker_collection,
                    ConfigurationErrorsCollector $configuration_errors,
                ): bool {
                    return true;
                }
            },
            new class implements VerifyTimeframeIsAligned {
                public function isTimeframeWellConfigured(
                    TrackerReference $tracker,
                    SourceTrackerCollection $source_tracker_collection,
                    ConfigurationErrorsCollector $configuration_errors,
                ): bool {
                    return true;
                }
            },
        );

        self::assertTrue(
            $verifier->areTrackerSemanticsWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers,
                $configuration_errors
            )
        );
        self::assertCount(0, $configuration_errors->getSemanticErrors());
    }

    public function testItReturnsFalseIfSomethingIsIncorrect(): void
    {
        $configuration_errors = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);

        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->willReturn(1);
        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->willReturn(1);
        $this->title_dao->method('getTrackerIdsWithoutSemanticTitleDefined')
            ->willReturn([101]);
        $this->description_dao->method('getTrackerIdsWithoutSemanticDescriptionDefined')
            ->willReturn([101]);

        $verifier = new SemanticsVerifier(
            $this->title_dao,
            $this->description_dao,
            new class implements VerifyStatusIsAligned {
                public function isStatusWellConfigured(
                    TrackerReference $tracker,
                    SourceTrackerCollection $source_tracker_collection,
                    ConfigurationErrorsCollector $configuration_errors,
                ): bool {
                    return false;
                }
            },
            new class implements VerifyTimeframeIsAligned {
                public function isTimeframeWellConfigured(
                    TrackerReference $tracker,
                    SourceTrackerCollection $source_tracker_collection,
                    ConfigurationErrorsCollector $configuration_errors,
                ): bool {
                    $configuration_errors->addSemanticError("Timeframe", "timeframe", []);
                    return false;
                }
            },
        );

        self::assertFalse(
            $verifier->areTrackerSemanticsWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers,
                $configuration_errors
            )
        );

        $collected_errors = $configuration_errors->getSemanticErrors();
        self::assertCount(3, $collected_errors);
        self::assertStringContainsString("Title", $collected_errors[0]->semantic_name);
        self::assertStringContainsString("Description", $collected_errors[1]->semantic_name);
        self::assertStringContainsString("Timeframe", $collected_errors[2]->semantic_name);
    }

    public function testItStopsAtFirstErrorFound(): void
    {
        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->willReturn(1);
        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->willReturn(1);
        $this->title_dao->method('getTrackerIdsWithoutSemanticTitleDefined')
            ->willReturn([101]);
        $this->description_dao->method('getTrackerIdsWithoutSemanticDescriptionDefined')
            ->willReturn([101]);

        $verifier = new SemanticsVerifier(
            $this->title_dao,
            $this->description_dao,
            new class implements VerifyStatusIsAligned {
                public function isStatusWellConfigured(
                    TrackerReference $tracker,
                    SourceTrackerCollection $source_tracker_collection,
                    ConfigurationErrorsCollector $configuration_errors,
                ): bool {
                    return false;
                }
            },
            new class implements VerifyTimeframeIsAligned {
                public function isTimeframeWellConfigured(
                    TrackerReference $tracker,
                    SourceTrackerCollection $source_tracker_collection,
                    ConfigurationErrorsCollector $configuration_errors,
                ): bool {
                    $configuration_errors->addSemanticError("Timeframe", "timeframe", []);
                    return false;
                }
            },
        );

        $configuration_errors = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        self::assertFalse(
            $verifier->areTrackerSemanticsWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers,
                $configuration_errors
            )
        );

        $collected_errors = $configuration_errors->getSemanticErrors();
        self::assertCount(1, $collected_errors);
        self::assertStringContainsString("Title", $collected_errors[0]->semantic_name);
    }
}
