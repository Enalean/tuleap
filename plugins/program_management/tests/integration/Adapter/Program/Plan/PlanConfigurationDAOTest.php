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

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\Admin\CollectionOfNewUserGroupsThatCanPrioritize;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewConfigurationTrackerIsValidCertificate;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewIterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewTrackerThatCanBePlanned;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewTrackerThatCanBePlannedCollection;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewUserGroupThatCanPrioritize;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramServiceIsEnabledCertificate;
use Tuleap\ProgramManagement\Domain\Workspace\NewUserGroupThatCanPrioritizeIsValidCertificate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanConfigurationDAOTest extends TestIntegrationTestCase
{
    private PlanConfigurationDAO $dao;

    protected function setUp(): void
    {
        $this->dao = new PlanConfigurationDAO();
    }

    public function testItSavesAndRetrievesThePlanConfiguration(): void
    {
        $program_id                   = 106;
        $program_increment_tracker_id = 27;
        $iteration_tracker_id         = 26;
        $epics_tracker_id             = 69;
        $enablers_tracker_id          = 99;

        $program_identifier = ProgramForAdministrationIdentifierBuilder::buildWithId($program_id);

        $user_group_id_granted_plan_permission = 668;

        $new_plan = new NewPlanConfiguration(
            NewProgramIncrementTracker::fromValidTrackerAndLabels(
                new NewConfigurationTrackerIsValidCertificate($program_increment_tracker_id, $program_identifier),
                'Releases',
                'release'
            ),
            $program_identifier,
            NewTrackerThatCanBePlannedCollection::fromTrackers(
                [
                    NewTrackerThatCanBePlanned::fromValidTracker(
                        new NewConfigurationTrackerIsValidCertificate($epics_tracker_id, $program_identifier)
                    ),
                    NewTrackerThatCanBePlanned::fromValidTracker(
                        new NewConfigurationTrackerIsValidCertificate($enablers_tracker_id, $program_identifier)
                    ),
                ]
            ),
            CollectionOfNewUserGroupsThatCanPrioritize::fromUserGroups(
                [
                    NewUserGroupThatCanPrioritize::fromValidUserGroup(
                        new NewUserGroupThatCanPrioritizeIsValidCertificate(
                            \ProjectUGroup::PROJECT_ADMIN,
                            $program_identifier
                        )
                    ),
                    NewUserGroupThatCanPrioritize::fromValidUserGroup(
                        new NewUserGroupThatCanPrioritizeIsValidCertificate(
                            $user_group_id_granted_plan_permission,
                            $program_identifier
                        )
                    ),
                ]
            ),
            Option::fromValue(
                NewIterationTrackerConfiguration::fromValidTrackerAndLabels(
                    new NewConfigurationTrackerIsValidCertificate($iteration_tracker_id, $program_identifier),
                    'Sprints',
                    'sprint'
                )
            )
        );

        $this->dao->save($new_plan);

        $program_identifier = ProgramIdentifier::fromServiceEnabled(
            new ProgramServiceIsEnabledCertificate($program_id)
        );

        $retrieved_plan = $this->dao->retrievePlan($program_identifier)->unwrapOr(null);
        if ($retrieved_plan === null) {
            throw new \Exception('Expected to retrieve a plan configuration');
        }
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
            [\ProjectUGroup::PROJECT_ADMIN, $user_group_id_granted_plan_permission],
            $retrieved_plan->user_group_ids_that_can_prioritize
        );
    }

    public function testItIgnoresEmptyListOfTrackersThatCanBePlanned(): void
    {
        $program_id                   = 175;
        $program_increment_tracker_id = 86;

        $program_identifier = ProgramForAdministrationIdentifierBuilder::buildWithId($program_id);

        $new_plan = new NewPlanConfiguration(
            NewProgramIncrementTracker::fromValidTrackerAndLabels(
                new NewConfigurationTrackerIsValidCertificate($program_increment_tracker_id, $program_identifier),
                null,
                null
            ),
            $program_identifier,
            NewTrackerThatCanBePlannedCollection::fromTrackers([]),
            CollectionOfNewUserGroupsThatCanPrioritize::fromUserGroups(
                [
                    NewUserGroupThatCanPrioritize::fromValidUserGroup(
                        new NewUserGroupThatCanPrioritizeIsValidCertificate(
                            \ProjectUGroup::PROJECT_ADMIN,
                            $program_identifier
                        )
                    ),
                ]
            ),
            Option::nothing(NewIterationTrackerConfiguration::class)
        );

        $this->dao->save($new_plan);

        $program_identifier = ProgramIdentifier::fromServiceEnabled(
            new ProgramServiceIsEnabledCertificate($program_id)
        );

        $retrieved_plan = $this->dao->retrievePlan($program_identifier)->unwrapOr(null);
        if ($retrieved_plan === null) {
            throw new \Exception('Expected to retrieve a plan configuration');
        }
        self::assertEmpty($retrieved_plan->tracker_ids_that_can_be_planned);
    }

    public function testItIgnoresEmptyListOfUserGroupsThatCanPrioritize(): void
    {
        $program_id                   = 132;
        $program_increment_tracker_id = 58;

        $program_identifier = ProgramForAdministrationIdentifierBuilder::buildWithId($program_id);

        $new_plan = new NewPlanConfiguration(
            NewProgramIncrementTracker::fromValidTrackerAndLabels(
                new NewConfigurationTrackerIsValidCertificate($program_increment_tracker_id, $program_identifier),
                null,
                null
            ),
            $program_identifier,
            NewTrackerThatCanBePlannedCollection::fromTrackers([
                NewTrackerThatCanBePlanned::fromValidTracker(new NewConfigurationTrackerIsValidCertificate(95, $program_identifier)),
            ]),
            CollectionOfNewUserGroupsThatCanPrioritize::fromUserGroups([]),
            Option::nothing(NewIterationTrackerConfiguration::class)
        );

        $this->dao->save($new_plan);

        $program_identifier = ProgramIdentifier::fromServiceEnabled(
            new ProgramServiceIsEnabledCertificate($program_id)
        );

        $retrieved_plan = $this->dao->retrievePlan($program_identifier)->unwrapOr(null);
        if ($retrieved_plan === null) {
            throw new \Exception('Expected to retrieve a plan configuration');
        }
        self::assertEmpty($retrieved_plan->user_group_ids_that_can_prioritize);
    }
}
