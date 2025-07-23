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

namespace Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewConfigurationTrackerIsValidCertificate;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;
use Tuleap\ProgramManagement\Domain\Workspace\NewUserGroupThatCanPrioritizeIsValidCertificate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\Program\Plan\RetrievePlanConfigurationStub;
use Tuleap\ProgramManagement\Tests\Stub\SaveNewPlanConfigurationStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanInheritanceHandlerTest extends TestCase
{
    private const SOURCE_PROGRAM_INCREMENT_TRACKER_ID          = 37;
    private const SOURCE_ITERATION_TRACKER_ID                  = 35;
    private const FIRST_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED  = 64;
    private const SECOND_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED = 65;
    /** @var array<int, NewConfigurationTrackerIsValidCertificate> */
    private array $tracker_mapping;
    private SaveNewPlanConfigurationStub $save_new_plan;
    private ProgramForAdministrationIdentifier $new_program;

    #[\Override]
    protected function setUp(): void
    {
        $this->new_program     = ProgramForAdministrationIdentifierBuilder::buildWithId(227);
        $this->tracker_mapping = [
            self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID          => new NewConfigurationTrackerIsValidCertificate(
                97,
                $this->new_program
            ),
            self::SOURCE_ITERATION_TRACKER_ID                  => new NewConfigurationTrackerIsValidCertificate(
                89,
                $this->new_program
            ),
            self::FIRST_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED  => new NewConfigurationTrackerIsValidCertificate(
                251,
                $this->new_program
            ),
            self::SECOND_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED => new NewConfigurationTrackerIsValidCertificate(
                252,
                $this->new_program
            ),
        ];
        $this->save_new_plan   = SaveNewPlanConfigurationStub::withCount();
    }

    /** @return Ok<null> | Err<Fault> */
    private function handle(): Ok|Err
    {
        $project_members_user_group_id         = \ProjectUGroup::PROJECT_MEMBERS;
        $user_group_id_granted_plan_permission = 822;

        $user_group_mapping = [
            $project_members_user_group_id         => new NewUserGroupThatCanPrioritizeIsValidCertificate(
                \ProjectUGroup::PROJECT_MEMBERS,
                $this->new_program,
            ),
            $user_group_id_granted_plan_permission => new NewUserGroupThatCanPrioritizeIsValidCertificate(
                1411,
                $this->new_program
            ),
        ];

        $source_program = ProgramIdentifierBuilder::buildWithId(135);
        $handler        = new PlanInheritanceHandler(
            RetrievePlanConfigurationStub::withPlanConfigurations(
                PlanConfiguration::fromRaw(
                    $source_program,
                    self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID,
                    'Releases',
                    'release',
                    Option::fromValue(self::SOURCE_ITERATION_TRACKER_ID),
                    'Cycles',
                    'cycle',
                    [self::FIRST_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED, self::SECOND_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED],
                    [$project_members_user_group_id, $user_group_id_granted_plan_permission]
                )
            ),
            new PlanConfigurationMapper(),
            $this->save_new_plan,
        );
        return $handler->handle(
            new ProgramInheritanceMapping(
                $source_program,
                $this->new_program,
                $this->tracker_mapping,
                $user_group_mapping,
            )
        );
    }

    public function testItReturnsErrAndDoesNotSaveWhenProgramIncrementTrackerIsNotFoundInMapping(): void
    {
        $this->tracker_mapping = [];

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertSame(0, $this->save_new_plan->getCallCount());
    }

    public function testItSavesMappedPlanConfigurationAndReturnsEmptyOk(): void
    {
        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value);
        self::assertSame(1, $this->save_new_plan->getCallCount());
    }
}
