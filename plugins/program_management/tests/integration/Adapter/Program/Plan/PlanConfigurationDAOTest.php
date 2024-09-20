<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramUserGroupCollection;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewIterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlannableTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanIterationChange;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramServiceIsEnabledCertificate;
use Tuleap\ProgramManagement\Tests\Stub\CheckNewIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\CheckNewPlannableTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\CheckNewProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramUserGroupStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class PlanConfigurationDAOTest extends TestIntegrationTestCase
{
    public function testItSavesAndRetrievesThePlanConfiguration(): void
    {
        $program_id                   = 106;
        $program_increment_tracker_id = 27;
        $iteration_tracker_id         = 26;
        $epics_tracker_id             = 69;
        $enablers_tracker_id          = 99;

        $program_identifier = ProgramForAdministrationIdentifier::fromProject(
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProjectPermissionStub::withAdministrator(),
            UserReferenceStub::withDefaults(),
            ProjectIdentifierStub::buildWithId($program_id)
        );

        $tracker_checker                       = CheckNewPlannableTrackerStub::withValidTracker();
        $project_administrators_user_group_id  = 4;
        $user_group_id_granted_plan_permission = 668;

        $new_plan = new NewPlanConfiguration(
            NewProgramIncrementTracker::fromId(
                CheckNewProgramIncrementTrackerStub::withValidTracker(),
                $program_increment_tracker_id,
                $program_identifier
            ),
            $program_id,
            [
                NewPlannableTracker::fromId($tracker_checker, $epics_tracker_id, $program_identifier),
                NewPlannableTracker::fromId($tracker_checker, $enablers_tracker_id, $program_identifier),
            ],
            ProgramUserGroupCollection::fromRawIdentifiers(
                RetrieveProgramUserGroupStub::withValidUserGroups(
                    $project_administrators_user_group_id,
                    $user_group_id_granted_plan_permission
                ),
                $program_identifier,
                [$program_id . '_' . $project_administrators_user_group_id, (string) $user_group_id_granted_plan_permission]
            ),
            'Releases',
            'release',
            NewIterationTrackerConfiguration::fromPlanIterationChange(
                CheckNewIterationTrackerStub::withValidTracker(),
                new PlanIterationChange($iteration_tracker_id, 'Sprints', 'sprint'),
                $program_identifier
            )
        );

        $dao = new PlanConfigurationDAO();
        $dao->save($new_plan);

        $program_identifier = ProgramIdentifier::fromServiceEnabled(new ProgramServiceIsEnabledCertificate($program_id));

        $retrieved_plan = $dao->retrievePlan($program_identifier);
        self::assertSame($program_id, $retrieved_plan->program_identifier->getId());
        self::assertSame($program_increment_tracker_id, $retrieved_plan->program_increment_tracker->getId());
        self::assertSame('Releases', $retrieved_plan->program_increment_labels->label);
        self::assertSame('release', $retrieved_plan->program_increment_labels->sub_label);
        self::assertSame($iteration_tracker_id, $retrieved_plan->iteration_tracker->unwrapOr(null)?->getId());
        self::assertSame('Sprints', $retrieved_plan->iteration_labels->label);
        self::assertSame('sprint', $retrieved_plan->iteration_labels->sub_label);
        self::assertEqualsCanonicalizing(
            [$epics_tracker_id, $enablers_tracker_id],
            $retrieved_plan->tracker_ids_that_can_be_planned
        );
        self::assertEqualsCanonicalizing(
            [$project_administrators_user_group_id, $user_group_id_granted_plan_permission],
            $retrieved_plan->user_group_ids_that_can_prioritize
        );
    }
}
