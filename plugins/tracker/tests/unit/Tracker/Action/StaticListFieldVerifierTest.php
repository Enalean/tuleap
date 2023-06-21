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
use Tuleap\Tracker\Test\Builders\TrackerFormElementListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;

final class StaticListFieldVerifierTest extends TestCase
{
    private StaticListFieldVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new StaticListFieldVerifier();
    }

    public function testItReturnsFalseWhenTheFieldIsNotAListField(): void
    {
        $field = TrackerFormElementStringFieldBuilder::aStringField(10)->build();

        self::assertFalse($this->verifier->isStaticListField($field));
    }

    public function testItReturnsFalseWhenTheFieldIsBoundToUsers(): void
    {
        $user_bind = TrackerFormElementListUserBindBuilder::aBind()->withUsers([
            UserTestBuilder::anActiveUser()->build(),
        ])->build();

        self::assertFalse($this->verifier->isStaticListField($user_bind->getField()));
    }

    public function testItReturnsTrueWhenTheFieldIsBoundToStaticValues(): void
    {
        $static_bind = TrackerFormElementListStaticBindBuilder::aBind()->withStaticValues([
            1 => "Open",
            2 => "Closed",
        ])->build();

        self::assertTrue($this->verifier->isStaticListField($static_bind->getField()));
    }
}
