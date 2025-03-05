<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter\Administration;

use Tuleap\Http\Server\NullServerRequest;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class RoleAssignmentFromRequestExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testExtractRoleAssignmentsFromRequest(): void
    {
        $request = (new NullServerRequest())->withParsedBody([
            'administrators' => ['102', '103'],
            'readers' => ['103', '104'],
        ]);

        $role_assignments_to_update = RoleAssignmentFromRequestExtractor::extractRoleAssignmentsFromRequest($request);

        self::assertEquals([102, 103], $role_assignments_to_update->getBaselineAdministratorsUserGroupsIds());
        self::assertEquals([103, 104], $role_assignments_to_update->getBaselineReadersUserGroupsIds());
    }
}
