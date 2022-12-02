<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

class WritersRetrieverTest extends TestCase
{
    public function testGetWritersUgroupIds(): void
    {
        $retriever = new WritersRetriever(ISearchByProjectAndPermissionStub::buildWithPermissions([], [103, 104]));

        self::assertEquals(
            [103, 104],
            $retriever->getWritersUgroupIds(ProjectTestBuilder::aProject()->build())
        );
    }

    public function testItDefaultsToProjectMembers(): void
    {
        $retriever = new WritersRetriever(ISearchByProjectAndPermissionStub::buildWithoutSpecificPermissions());

        self::assertEquals(
            [\ProjectUGroup::PROJECT_MEMBERS],
            $retriever->getWritersUgroupIds(ProjectTestBuilder::aProject()->build())
        );
    }
}
