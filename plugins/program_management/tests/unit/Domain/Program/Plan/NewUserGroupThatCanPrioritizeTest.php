<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramUserGroupDoesNotExistException;
use Tuleap\ProgramManagement\Domain\Workspace\NewUserGroupThatCanPrioritizeIsValidCertificate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramUserGroupStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewUserGroupThatCanPrioritizeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramForAdministrationIdentifier $program;

    protected function setUp(): void
    {
        $this->program = ProgramForAdministrationIdentifierBuilder::build();
    }

    public function testRejectIfUgroupDoesNotExist(): void
    {
        $this->expectException(ProgramUserGroupDoesNotExistException::class);
        NewUserGroupThatCanPrioritize::fromId(
            RetrieveProgramUserGroupStub::withNotValidUserGroup(),
            '123',
            $this->program
        );
    }

    public function testItBuildsFromId(): void
    {
        $group = NewUserGroupThatCanPrioritize::fromId(
            RetrieveProgramUserGroupStub::withValidUserGroups(123),
            '123',
            $this->program
        );
        self::assertEquals(123, $group->id);
    }

    public function testItBuildsFromValidUserGroup(): void
    {
        $new_group = NewUserGroupThatCanPrioritize::fromValidUserGroup(
            new NewUserGroupThatCanPrioritizeIsValidCertificate(\ProjectUGroup::PROJECT_ADMIN, $this->program)
        );
        self::assertSame(\ProjectUGroup::PROJECT_ADMIN, $new_group->id);
    }
}
