<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;

final class UserGroupOpenListFieldVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsFalseWhenFieldIsNotAnOpenListField(): void
    {
        self::assertFalse(
            (new UserGroupOpenListFieldVerifier())->isUserGroupOpenListField(
                ListFieldBuilder::aListField(1)->build()
            )
        );
    }

    public function testItReturnsFalseWhenFieldIsNotBoundToUserGroups(): void
    {
        self::assertFalse(
            (new UserGroupOpenListFieldVerifier())->isUserGroupOpenListField(
                ListUserBindBuilder::aUserBind(
                    OpenListFieldBuilder::anOpenListField()->build()
                )->build()->getField()
            )
        );
    }

    public function testItReturnsTrueWhenFieldIsBoundToUserGroups(): void
    {
        self::assertTrue(
            (new UserGroupOpenListFieldVerifier())->isUserGroupOpenListField(
                ListUserGroupBindBuilder::aUserGroupBind(
                    OpenListFieldBuilder::anOpenListField()->build()
                )->build()->getField()
            )
        );
    }
}
