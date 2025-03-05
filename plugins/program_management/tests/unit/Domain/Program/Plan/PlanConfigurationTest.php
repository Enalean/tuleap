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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramServiceIsEnabledCertificate;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanConfigurationTest extends TestCase
{
    public function testItBuilds(): void
    {
        $program_id         = 169;
        $program_identifier = ProgramIdentifier::fromServiceEnabled(new ProgramServiceIsEnabledCertificate($program_id));

        $program_increment_tracker_id          = 26;
        $iteration_tracker_id                  = 57;
        $epics_tracker_id                      = 768;
        $enablers_tracker_id                   = 350;
        $project_administrators_user_group_id  = 4;
        $user_group_id_granted_plan_permission = 934;

        $plan = PlanConfiguration::fromRaw(
            $program_identifier,
            $program_increment_tracker_id,
            'Releases',
            'release',
            Option::fromValue($iteration_tracker_id),
            'Sprints',
            'sprint',
            [$epics_tracker_id, $enablers_tracker_id],
            [$project_administrators_user_group_id, $user_group_id_granted_plan_permission],
        );

        self::assertSame($program_id, $plan->program_identifier->getId());
        self::assertSame($program_increment_tracker_id, $plan->program_increment_tracker->getId());
        self::assertSame('Releases', $plan->program_increment_labels->label);
        self::assertSame('release', $plan->program_increment_labels->sub_label);
        self::assertSame($iteration_tracker_id, $plan->iteration_tracker->unwrapOr(null)?->getId());
        self::assertSame('Sprints', $plan->iteration_labels->label);
        self::assertSame('sprint', $plan->iteration_labels->sub_label);
        self::assertEqualsCanonicalizing(
            [$epics_tracker_id, $enablers_tracker_id],
            $plan->tracker_ids_that_can_be_planned
        );
        self::assertEqualsCanonicalizing(
            [$project_administrators_user_group_id, $user_group_id_granted_plan_permission],
            $plan->user_group_ids_that_can_prioritize
        );
    }
}
