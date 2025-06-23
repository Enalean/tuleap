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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackers;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Tests\Builder\SynchronizedFieldReferencesBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectFromTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFieldPermissionsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID = 758;
    private WorkflowVerifier $verifier;
    /**
     * @var MockObject&\Workflow_Dao
     */
    private $workflow_dao;
    /**
     * @var MockObject&\Tracker_Rule_Date_Dao
     */
    private $rule_date_dao;
    /**
     * @var MockObject&\Tracker_Rule_List_Dao
     */
    private $rule_list_dao;
    /**
     * @var MockObject&\TrackerFactory
     */
    private $tracker_factory;
    private RetrieveTrackerFromFieldStub $retrieve_tracker_from_field;
    private VerifyFieldPermissionsStub $retrieve_field_permissions;
    private SynchronizedFieldFromProgramAndTeamTrackersCollection $collection;
    private TrackerCollection $mirrored_program_increment_trackers;
    private ConfigurationErrorsCollector $errors_collector;
    private UserIdentifierStub $user;

    protected function setUp(): void
    {
        $this->workflow_dao                = $this->createMock(\Workflow_Dao::class);
        $this->rule_date_dao               = $this->createMock(\Tracker_Rule_Date_Dao::class);
        $this->rule_list_dao               = $this->createMock(\Tracker_Rule_List_Dao::class);
        $this->tracker_factory             = $this->createMock(\TrackerFactory::class);
        $this->retrieve_tracker_from_field = RetrieveTrackerFromFieldStub::withTracker(
            TrackerReferenceStub::withDefaults()
        );
        $this->verifier                    = new WorkflowVerifier(
            $this->workflow_dao,
            $this->rule_date_dao,
            $this->rule_list_dao,
            $this->tracker_factory
        );
        $this->retrieve_field_permissions  = VerifyFieldPermissionsStub::withValidField();

        $this->user = UserIdentifierStub::buildGenericUser();

        $this->collection    = new SynchronizedFieldFromProgramAndTeamTrackersCollection(
            MessageLog::buildFromLogger(new NullLogger()),
            $this->retrieve_tracker_from_field,
            $this->retrieve_field_permissions,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );
        $synchronized_fields = new SynchronizedFieldFromProgramAndTeamTrackers(
            SynchronizedFieldReferencesBuilder::build()
        );
        $this->collection->add($synchronized_fields);

        $teams = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(785)
        );

        $this->errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);

        $this->mirrored_program_increment_trackers = TrackerCollection::buildRootPlanningMilestoneTrackers(
            RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
                TrackerReferenceStub::withId(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID)
            ),
            $teams,
            $this->user,
            $this->errors_collector
        );
    }

    public function testConsiderTrackerTeamsAreValidWhenAllRulesAreVerified(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_date_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_list_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->tracker_factory->method('getTrackerById')
            ->with(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID)
            ->willReturn(
                TrackerTestBuilder::aTracker()
                    ->withId(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID)
                    ->withName('tracker')
                    ->build()
            );

        self::assertTrue(
            $this->verifier->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $this->mirrored_program_increment_trackers,
                $this->collection,
                $this->errors_collector
            )
        );
    }

    public function testRejectsWhenSomeWorkflowTransitionRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn(
            [['tracker_id' => self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, 'field_id' => 963]]
        );
        $this->tracker_factory->method('getTrackerById')
            ->with(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID)
            ->willReturn(
                $this->getTrackerWithIdWithGenericProject(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, 'tracker')
            );

        self::assertFalse(
            $this->verifier->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $this->mirrored_program_increment_trackers,
                $this->collection,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
            )
        );
    }

    public function testRejectsWhenSomeDateRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_date_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn(
            [self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID]
        );

        $this->tracker_factory->method('getTrackerById')
            ->with(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID)
            ->willReturn(
                $this->getTrackerWithIdWithGenericProject(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, 'tracker')
            );

        self::assertFalse(
            $this->verifier->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $this->mirrored_program_increment_trackers,
                $this->collection,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
            )
        );
    }

    public function testRejectsWhenSomeListRulesAreDefinedWithASynchronizedField(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_date_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([]);
        $this->rule_list_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn(
            [self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID]
        );

        $this->tracker_factory->method('getTrackerById')
            ->with(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID)
            ->willReturn(
                $this->getTrackerWithIdWithGenericProject(self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, 'tracker')
            );

        self::assertFalse(
            $this->verifier->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $this->mirrored_program_increment_trackers,
                $this->collection,
                $this->errors_collector
            )
        );
    }

    public function testCollectsAllErrors(): void
    {
        $this->workflow_dao->method('searchWorkflowsByFieldIDsAndTrackerIDs')->willReturn(
            [['tracker_id' => self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID, 'field_id' => 963]]
        );
        $this->rule_date_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn([123]);
        $this->rule_list_dao->method('searchTrackersWithRulesByFieldIDsAndTrackerIDs')->willReturn(
            [self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID]
        );
        $this->tracker_factory->method('getTrackerById')
            ->willReturnOnConsecutiveCalls(
                $this->getTrackerWithIdWithGenericProject(
                    self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID,
                    'tracker A'
                ),
                $this->getTrackerWithIdWithGenericProject(123, 'tracker B'),
                $this->getTrackerWithIdWithGenericProject(
                    self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID,
                    'tracker A'
                )
            );

        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        self::assertFalse(
            $this->verifier->areWorkflowsNotUsedWithSynchronizedFieldsInTeamTrackers(
                $this->mirrored_program_increment_trackers,
                $this->collection,
                $errors_collector
            )
        );

        self::assertSame(
            self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID,
            $errors_collector->getFieldDependencyError()[0]->tracker_id
        );
        self::assertSame(123, $errors_collector->getTransitionRuleDateError()[0]->tracker_id);
        self::assertSame(
            self::FIRST_MIRRORED_PROGRAM_INCREMENT_TRACKER_ID,
            $errors_collector->getTransitionRuleError()[0]->tracker_id
        );
    }

    protected function getTrackerWithIdWithGenericProject(int $tracker_id, string $tracker_name): \Tuleap\Tracker\Tracker
    {
        $project = new \Project(['group_id' => 101, 'group_name' => 'My project', 'unix_group_name' => 'my_project', 'icon_codepoint' => '']);
        return TrackerTestBuilder::aTracker()->withId($tracker_id)->withName($tracker_name)->withProject($project)->build();
    }
}
