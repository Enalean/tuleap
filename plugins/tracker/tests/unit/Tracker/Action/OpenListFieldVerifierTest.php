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

use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

final class OpenListFieldVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private OpenListFieldVerifier $open_list_verifier;

    protected function setUp(): void
    {
        $this->open_list_verifier = new OpenListFieldVerifier();
    }

    public function testIsNotAnOpenListField(): void
    {
        $field = StringFieldBuilder::aStringField(1)->build();
        self::assertFalse($this->open_list_verifier->isAnOpenListField($field));
    }

    public function testIsAnOpenListField(): void
    {
        $bind = OpenListFieldBuilder::aBind()->buildUserBind();
        self::assertTrue($this->open_list_verifier->isAnOpenListField($bind->getField()));
    }
}
