<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin;

use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramUserGroupStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CollectionOfNewUserGroupsThatCanPrioritizeTest extends TestCase
{
    public function testItBuildsFromRawIdentifiers(): void
    {
        $program_id = 102;
        $program    = ProgramForAdministrationIdentifierBuilder::buildWithId($program_id);

        $collection = CollectionOfNewUserGroupsThatCanPrioritize::fromRawIdentifiers(
            RetrieveProgramUserGroupStub::withValidUserGroups(
                \ProjectUGroup::PROJECT_MEMBERS,
                \ProjectUGroup::PROJECT_ADMIN
            ),
            $program,
            [$program_id . '_' . \ProjectUGroup::PROJECT_MEMBERS, $program_id . '_' . \ProjectUGroup::PROJECT_ADMIN]
        );

        self::assertEqualsCanonicalizing(
            [\ProjectUGroup::PROJECT_MEMBERS, \ProjectUGroup::PROJECT_ADMIN],
            $collection->getUserGroupIds()
        );
    }
}
