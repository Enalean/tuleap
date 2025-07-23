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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\VerifyFieldPermissions;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanSubmit;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamProjectsCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyRequiredFieldsLimitedToSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifySemanticsAreConfiguredStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifySynchronizedFieldsAreNotUsedInWorkflowStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveMirroredProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectFromTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerFromFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFieldPermissionsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanSubmitStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimeboxCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private GatherSynchronizedFields $fields_adapter;
    private UserIdentifier $user;
    private TrackerReference $program_increment_tracker;
    private RetrieveTrackerFromFieldStub $retrieve_tracker_from_field;
    private VerifyUserCanSubmit $user_can_submit;
    private TrackerCollection $team_trackers;
    private SourceTrackerCollection $program_and_team_trackers;

    #[\Override]
    protected function setUp(): void
    {
        $this->program_increment_tracker = TrackerReferenceStub::withDefaults();

        $this->retrieve_tracker_from_field = RetrieveTrackerFromFieldStub::withTracker($this->program_increment_tracker);

        $this->fields_adapter  = GatherSynchronizedFieldsStub::withFieldsPreparations(
            SynchronizedFieldsStubPreparation::withAllFields(770, 362, 544, 436, 341, 245),
            SynchronizedFieldsStubPreparation::withAllFields(610, 360, 227, 871, 623, 440),
            SynchronizedFieldsStubPreparation::withAllFields(914, 977, 235, 435, 148, 475),
        );
        $this->user_can_submit = VerifyUserCanSubmitStub::userCanSubmit();

        $this->user = UserIdentifierStub::buildGenericUser();

        $teams = TeamProjectsCollectionBuilder::withProjects(
            ProjectReferenceStub::withId(104),
            ProjectReferenceStub::withId(142),
        );

        $this->team_trackers = TrackerCollection::buildRootPlanningMilestoneTrackers(
            RetrieveMirroredProgramIncrementTrackerStub::withValidTrackers(
                TrackerReferenceStub::withId(71),
                TrackerReferenceStub::withId(6),
            ),
            $teams,
            $this->user,
            new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
        );

        $this->program_and_team_trackers = SourceTrackerCollection::fromProgramAndTeamTrackers(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($this->program_increment_tracker),
            ProgramIdentifierBuilder::build(),
            $this->team_trackers,
            $this->user
        );
    }

    private function getChecker(
        VerifyFieldPermissions $retrieve_field_permissions,
        VerifySemanticsAreConfigured $check_semantic,
        VerifyRequiredFieldsLimitedToSynchronizedFields $check_required_field,
        VerifySynchronizedFieldsAreNotUsedInWorkflow $check_workflow,
    ): TimeboxCreatorChecker {
        $field_collection_builder = new SynchronizedFieldFromProgramAndTeamTrackersCollectionBuilder(
            $this->fields_adapter,
            MessageLog::buildFromLogger(new NullLogger()),
            $this->retrieve_tracker_from_field,
            $retrieve_field_permissions,
            RetrieveProjectFromTrackerStub::buildGeneric()
        );

        return new TimeboxCreatorChecker(
            $field_collection_builder,
            $check_semantic,
            $check_required_field,
            $check_workflow,
            $this->retrieve_tracker_from_field,
            RetrieveProjectFromTrackerStub::buildGeneric(),
            $this->user_can_submit
        );
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        self::assertTrue(
            $this->getChecker(
                VerifyFieldPermissionsStub::withValidField(),
                VerifySemanticsAreConfiguredStub::withValidSemantics(),
                VerifyRequiredFieldsLimitedToSynchronizedFieldsStub::withValidField(),
                VerifySynchronizedFieldsAreNotUsedInWorkflowStub::withoutAWorkflow()
            )->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $this->program_and_team_trackers,
                $this->team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true)
            )
        );
    }

    public function testItReturnsFalseIfSemanticsAreNotWellConfigured(): void
    {
        self::assertFalse(
            $this->getChecker(
                VerifyFieldPermissionsStub::withValidField(),
                VerifySemanticsAreConfiguredStub::withInvalidSemantics(),
                VerifyRequiredFieldsLimitedToSynchronizedFieldsStub::withValidField(),
                VerifySynchronizedFieldsAreNotUsedInWorkflowStub::withoutAWorkflow()
            )->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $this->program_and_team_trackers,
                $this->team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
            )
        );
    }

    public function testItReturnsFalseIfUserCannotSubmitArtifact(): void
    {
        $this->user_can_submit = VerifyUserCanSubmitStub::userCanNotSubmit();

        self::assertFalse(
            $this->getChecker(
                VerifyFieldPermissionsStub::withValidField(),
                VerifySemanticsAreConfiguredStub::withValidSemantics(),
                VerifyRequiredFieldsLimitedToSynchronizedFieldsStub::withValidField(),
                VerifySynchronizedFieldsAreNotUsedInWorkflowStub::withoutAWorkflow()
            )->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $this->program_and_team_trackers,
                $this->team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
            )
        );
    }

    public function testItReturnsFalseIfFieldsCantBeExtractedFromMilestoneTrackers(): void
    {
        $this->fields_adapter = GatherSynchronizedFieldsStub::withError();

        self::assertFalse(
            $this->getChecker(
                VerifyFieldPermissionsStub::withValidField(),
                VerifySemanticsAreConfiguredStub::withValidSemantics(),
                VerifyRequiredFieldsLimitedToSynchronizedFieldsStub::withValidField(),
                VerifySynchronizedFieldsAreNotUsedInWorkflowStub::withoutAWorkflow()
            )->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $this->program_and_team_trackers,
                $this->team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
            )
        );
    }

    public function testItReturnsFalseIfUserCantSubmitOneArtifactLink(): void
    {
        self::assertFalse(
            $this->getChecker(
                VerifyFieldPermissionsStub::userCantSubmit(),
                VerifySemanticsAreConfiguredStub::withValidSemantics(),
                VerifyRequiredFieldsLimitedToSynchronizedFieldsStub::withValidField(),
                VerifySynchronizedFieldsAreNotUsedInWorkflowStub::withoutAWorkflow()
            )->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $this->program_and_team_trackers,
                $this->team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
            )
        );
    }

    public function testItReturnsFalseIfTrackersHaveRequiredFieldsThatCannotBeSynchronized(): void
    {
        self::assertFalse(
            $this->getChecker(
                VerifyFieldPermissionsStub::userCantSubmit(),
                VerifySemanticsAreConfiguredStub::withValidSemantics(),
                VerifyRequiredFieldsLimitedToSynchronizedFieldsStub::withRequiredField(),
                VerifySynchronizedFieldsAreNotUsedInWorkflowStub::withoutAWorkflow()
            )->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $this->program_and_team_trackers,
                $this->team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false)
            )
        );
    }

    public function testItReturnsFalseIfTeamTrackersAreUsingSynchronizedFieldsInWorkflowRules(): void
    {
        self::assertFalse(
            $this->getChecker(
                VerifyFieldPermissionsStub::withValidField(),
                VerifySemanticsAreConfiguredStub::withValidSemantics(),
                VerifyRequiredFieldsLimitedToSynchronizedFieldsStub::withValidField(),
                VerifySynchronizedFieldsAreNotUsedInWorkflowStub::withAnActiveWorkflow()
            )->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $this->program_and_team_trackers,
                $this->team_trackers,
                $this->user,
                new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true)
            )
        );
    }

    public function testItRunAllTestsEvenIfErrorsAreFound(): void
    {
        $configuration_errors = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        self::assertFalse(
            $this->getChecker(
                VerifyFieldPermissionsStub::userCantSubmit(),
                VerifySemanticsAreConfiguredStub::withInvalidSemantics(),
                VerifyRequiredFieldsLimitedToSynchronizedFieldsStub::withRequiredField(),
                VerifySynchronizedFieldsAreNotUsedInWorkflowStub::withAnActiveWorkflow()
            )->canTimeboxBeCreated(
                $this->program_increment_tracker,
                $this->program_and_team_trackers,
                $this->team_trackers,
                $this->user,
                $configuration_errors
            )
        );

        self::assertTrue($configuration_errors->hasError());
    }
}
