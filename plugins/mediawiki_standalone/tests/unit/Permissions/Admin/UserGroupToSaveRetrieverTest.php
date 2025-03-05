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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\UGroupRetrieverStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class UserGroupToSaveRetrieverTest extends TestCase
{
    public function testGetUserGroupsThrowsExceptionIfUserGroupIsNotFoundInProject(): void
    {
        $ugroup_retriever = UGroupRetrieverStub::buildWithUserGroups(
            ProjectUGroupTestBuilder::buildProjectMembers(),
            ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('Developers')->build(),
        );

        $retriever = new UserGroupToSaveRetriever($ugroup_retriever);

        $this->expectException(UnknownUserGroupException::class);
        $retriever->getUserGroups(
            ProjectTestBuilder::aProject()->build(),
            [102, 103]
        );
    }

    public function testGetUserGroups(): void
    {
        $developers = ProjectUGroupTestBuilder::aCustomUserGroup(102)->withName('Developers')->build();
        $qa         = ProjectUGroupTestBuilder::aCustomUserGroup(103)->withName('QA')->build();

        $ugroup_retriever = UGroupRetrieverStub::buildWithUserGroups(
            ProjectUGroupTestBuilder::buildProjectMembers(),
            $developers,
            $qa,
        );

        $retriever = new UserGroupToSaveRetriever($ugroup_retriever);

        self::assertEquals(
            [$developers, $qa],
            $retriever->getUserGroups(
                ProjectTestBuilder::aProject()->build(),
                [102, 103]
            )
        );
    }
}
