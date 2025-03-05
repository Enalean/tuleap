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
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;
use Tuleap\ProgramManagement\Domain\Workspace\NewUserGroupThatCanPrioritizeIsValidCertificate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanConfigurationMapperTest extends TestCase
{
    private const SOURCE_PROGRAM_INCREMENT_TRACKER_ID                 = 37;
    private const SOURCE_ITERATION_TRACKER_ID                         = 35;
    private const FIRST_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED         = 64;
    private const SECOND_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED        = 65;
    private const NEW_PROGRAM_ID                                      = 227;
    private const FIRST_SOURCE_USER_GROUP_ID_GRANTED_PLAN_PERMISSION  = \ProjectUGroup::PROJECT_MEMBERS;
    private const SECOND_SOURCE_USER_GROUP_ID_GRANTED_PLAN_PERMISSION = 822;
    /** @var array<int, NewConfigurationTrackerIsValidCertificate> */
    private array $tracker_mapping;
    /** @var Option<int> */
    private Option $source_iteration_tracker_id;
    /** @var array<int, NewUserGroupThatCanPrioritizeIsValidCertificate> */
    private array $user_group_mapping;
    private ProgramForAdministrationIdentifier $new_program;

    protected function setUp(): void
    {
        $this->new_program                 = ProgramForAdministrationIdentifierBuilder::buildWithId(
            self::NEW_PROGRAM_ID
        );
        $this->tracker_mapping             = [
            self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID => new NewConfigurationTrackerIsValidCertificate(
                97,
                $this->new_program
            ),
            self::SOURCE_ITERATION_TRACKER_ID         => new NewConfigurationTrackerIsValidCertificate(
                89,
                $this->new_program
            ),
        ];
        $this->user_group_mapping          = [];
        $this->source_iteration_tracker_id = Option::fromValue(self::SOURCE_ITERATION_TRACKER_ID);
    }

    /** @return Ok<NewPlanConfiguration> | Err<Fault> */
    private function map(): Ok|Err
    {
        $source_program = ProgramIdentifierBuilder::buildWithId(135);
        $mapper         = new PlanConfigurationMapper();
        return $mapper->mapFromTemplateProgramToNewProgram(
            new ProgramInheritanceMapping(
                $source_program,
                $this->new_program,
                $this->tracker_mapping,
                $this->user_group_mapping
            ),
            PlanConfiguration::fromRaw(
                $source_program,
                self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID,
                'Releases',
                'release',
                $this->source_iteration_tracker_id,
                'Cycles',
                'cycle',
                [self::FIRST_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED, self::SECOND_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED],
                [self::FIRST_SOURCE_USER_GROUP_ID_GRANTED_PLAN_PERMISSION, self::SECOND_SOURCE_USER_GROUP_ID_GRANTED_PLAN_PERMISSION]
            )
        );
    }

    public function testItReturnsErrWhenProgramIncrementTrackerIsNotFoundInMapping(): void
    {
        $this->tracker_mapping = [];

        $result = $this->map();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ProgramIncrementTrackerNotFoundInMappingFault::class, $result->error);
    }

    public function testItDoesNotMapEmptyConfigurationForIterations(): void
    {
        $this->source_iteration_tracker_id = Option::nothing(\Psl\Type\int());

        $result = $this->map();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->value->iteration_tracker->isNothing());
    }

    public function testItDoesNotMapIterationConfigurationWhenTrackerIsNotFoundInMapping(): void
    {
        $this->source_iteration_tracker_id = Option::fromValue(70);

        $result = $this->map();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->value->iteration_tracker->isNothing());
    }

    public function testItDoesNotMapTrackersThatCanBePlannedWhenNotFoundInMapping(): void
    {
        $result = $this->map();

        self::assertTrue(Result::isOk($result));
        self::assertEmpty($result->value->trackers_that_can_be_planned->getTrackerIds());
    }

    public function testItDoesNotMapUserGroupsThatCanPrioritizeWhenNotFoundInMapping(): void
    {
        $result = $this->map();

        self::assertTrue(Result::isOk($result));
        self::assertEmpty($result->value->user_groups_that_can_prioritize->getUserGroupIds());
    }

    public function testItMapsConfigurationAndReturnsIt(): void
    {
        $this->tracker_mapping[self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID]          = new NewConfigurationTrackerIsValidCertificate(
            88,
            $this->new_program
        );
        $this->tracker_mapping[self::SOURCE_ITERATION_TRACKER_ID]                  = new NewConfigurationTrackerIsValidCertificate(
            123,
            $this->new_program
        );
        $this->tracker_mapping[self::FIRST_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED]  = new NewConfigurationTrackerIsValidCertificate(
            172,
            $this->new_program
        );
        $this->tracker_mapping[self::SECOND_SOURCE_TRACKER_ID_THAT_CAN_BE_PLANNED] = new NewConfigurationTrackerIsValidCertificate(
            173,
            $this->new_program
        );

        $this->user_group_mapping[self::FIRST_SOURCE_USER_GROUP_ID_GRANTED_PLAN_PERMISSION]  = new NewUserGroupThatCanPrioritizeIsValidCertificate(
            \ProjectUGroup::PROJECT_MEMBERS,
            $this->new_program
        );
        $this->user_group_mapping[self::SECOND_SOURCE_USER_GROUP_ID_GRANTED_PLAN_PERMISSION] = new NewUserGroupThatCanPrioritizeIsValidCertificate(
            951,
            $this->new_program
        );

        $result = $this->map();

        self::assertTrue(Result::isOk($result));
        self::assertSame(self::NEW_PROGRAM_ID, $result->value->program->id);
        self::assertSame(88, $result->value->program_increment_tracker->id);
        self::assertSame('Releases', $result->value->program_increment_tracker->label);
        self::assertSame('release', $result->value->program_increment_tracker->sub_label);
        $iteration = $result->value->iteration_tracker->unwrapOr(null);
        self::assertSame(123, $iteration?->id);
        self::assertSame('Cycles', $iteration?->label);
        self::assertSame('cycle', $iteration?->sub_label);
        self::assertEqualsCanonicalizing([172, 173], $result->value->trackers_that_can_be_planned->getTrackerIds());
        self::assertEqualsCanonicalizing(
            [\ProjectUGroup::PROJECT_MEMBERS, 951],
            $result->value->user_groups_that_can_prioritize->getUserGroupIds()
        );
    }
}
