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

namespace Tuleap\Document\Tests\Action;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Action\UserListFieldVerifier;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;

final class UserListFieldVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsFalseWhenFieldIsNotAListField(): void
    {
        $field = TrackerFormElementStringFieldBuilder::aStringField(10)->withName('summary')->build();

        self::assertFalse((new UserListFieldVerifier())->isUserListField($field));
    }

    public function testItReturnsFalseWhenFieldIsAListFieldBoundToStaticValues(): void
    {
        $static_bind = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(422)->build()
        )->withStaticValues([
            1 => "Open",
            2 => "Closed",
        ])->build();

        self::assertFalse((new UserListFieldVerifier())->isUserListField($static_bind->getField()));
    }

    public function testItReturnsTrueWhenFieldIsAListFieldBoundToUsers(): void
    {
        $user_bind = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(552)->build()
        )->withUsers([
            UserTestBuilder::anActiveUser()->build(),
        ])->build();

        self::assertTrue((new UserListFieldVerifier())->isUserListField($user_bind->getField()));
    }
}
