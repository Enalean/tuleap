<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\OpenListStaticValueBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_List_OpenValueTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testJSon(): void
    {
        $id    = 123;
        $label = 'Reopen';
        $value = OpenListStaticValueBuilder::aStaticValue($label)->withId($id)->build();
        self::assertEquals(
            '{"id":123,"value":"o123","caption":"Reopen","rest_value":"Reopen"}',
            json_encode($value->fetchForOpenListJson())
        );
    }
}
