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

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Tracker\Action;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Action\StaticListFieldVerifier;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

final class StaticListFieldVerifierTest extends TestCase
{
    private StaticListFieldVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new StaticListFieldVerifier();
    }

    public function testItReturnsFalseWhenTheFieldIsNotAListField(): void
    {
        $field = StringFieldBuilder::aStringField(10)->build();

        self::assertFalse($this->verifier->isStaticListField($field));
    }

    public function testItReturnsFalseWhenTheFieldIsBoundToUsers(): void
    {
        $user_bind = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(761)->build()
        )->withUsers([
            UserTestBuilder::anActiveUser()->build(),
        ])->build();

        self::assertFalse($this->verifier->isStaticListField($user_bind->getField()));
    }

    public function testItReturnsTrueWhenTheFieldIsBoundToStaticValues(): void
    {
        $static_bind = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(977)->build()
        )->withStaticValues([
            1 => "Open",
            2 => "Closed",
        ])->build();

        self::assertTrue($this->verifier->isStaticListField($static_bind->getField()));
    }
}
