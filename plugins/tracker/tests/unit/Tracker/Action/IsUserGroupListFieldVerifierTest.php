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
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;

class IsUserGroupListFieldVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private IsUserGroupListFieldVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new IsUserGroupListFieldVerifier();
    }

    public function testItReturnsFalseWhenTheFieldIsNotAListField(): void
    {
        $field = TrackerFormElementStringFieldBuilder::aStringField(1)->build();

        self::assertFalse($this->verifier->isUserGroupListField($field));
    }

    public function testItReturnsFalseWhenTheFieldIsNotBoundToUserGroups(): void
    {
        $user_bind = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(1)->withName('assigned_to')->build()
        )->build();

        self::assertFalse($this->verifier->isUserGroupListField($user_bind->getField()));
    }

    public function testItReturnsTrueWhenTheFieldIsBoundToUserGroups(): void
    {
        $user_group_bind = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(1)->withName('cc')->build()
        )->build();

        self::assertTrue($this->verifier->isUserGroupListField($user_group_bind->getField()));
    }
}
