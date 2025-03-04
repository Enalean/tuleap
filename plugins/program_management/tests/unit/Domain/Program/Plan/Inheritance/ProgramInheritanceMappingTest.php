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

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewConfigurationTrackerIsValidCertificate;
use Tuleap\ProgramManagement\Domain\Workspace\NewUserGroupThatCanPrioritizeIsValidCertificate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramInheritanceMappingTest extends TestCase
{
    /** @var array<int, NewConfigurationTrackerIsValidCertificate> */
    private array $tracker_mapping;
    /** @var array<int, NewUserGroupThatCanPrioritizeIsValidCertificate> */
    private array $user_group_mapping;
    private ProgramForAdministrationIdentifier $new_program;

    protected function setUp(): void
    {
        $this->tracker_mapping    = [];
        $this->user_group_mapping = [];
        $this->new_program        = ProgramForAdministrationIdentifierBuilder::buildWithId(216);
    }

    private function buildMapping(): ProgramInheritanceMapping
    {
        return new ProgramInheritanceMapping(
            ProgramIdentifierBuilder::buildWithId(142),
            $this->new_program,
            $this->tracker_mapping,
            $this->user_group_mapping,
        );
    }

    public function testItReturnsMappedTrackerId(): void
    {
        $mapped_tracker        = new NewConfigurationTrackerIsValidCertificate(152, $this->new_program);
        $this->tracker_mapping = [87 => $mapped_tracker];
        self::assertSame($mapped_tracker, $this->buildMapping()->getMappedTrackerId(87)->unwrapOr(null));
    }

    public function testItReturnsNothingWhenTrackerMappingDoesNotExist(): void
    {
        $this->tracker_mapping = [];
        self::assertTrue($this->buildMapping()->getMappedTrackerId(84)->isNothing());
    }

    public function testItReturnsMappedUserGroupId(): void
    {
        $mapped_user_group        = new NewUserGroupThatCanPrioritizeIsValidCertificate(290, $this->new_program);
        $this->user_group_mapping = [253 => $mapped_user_group];
        self::assertSame($mapped_user_group, $this->buildMapping()->getMappedUserGroupId(253)->unwrapOr(null));
    }

    public function testItReturnsNothingWhenUserGroupMappingDoesNotExist(): void
    {
        $this->user_group_mapping = [];
        self::assertTrue($this->buildMapping()->getMappedUserGroupId(905)->isNothing());
    }
}
