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


final class OpenListFieldsCompatibilityVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsFalseWhenTheFieldsDoNotHaveTheSameBindType(): void
    {
        self::assertFalse(
            (new OpenListFieldsCompatibilityVerifier())->areOpenListFieldsCompatible(
                $this->getOpenListFieldWithBindType(\Tracker_FormElement_Field_List_Bind_Static::class),
                $this->getOpenListFieldWithBindType(\Tracker_FormElement_Field_List_Bind_Users::class)
            )
        );
    }

    public function testItReturnsTrueWhenTheFieldsHaveTheSameBindType(): void
    {
        self::assertTrue(
            (new OpenListFieldsCompatibilityVerifier())->areOpenListFieldsCompatible(
                $this->getOpenListFieldWithBindType(\Tracker_FormElement_Field_List_Bind_Users::class),
                $this->getOpenListFieldWithBindType(\Tracker_FormElement_Field_List_Bind_Users::class)
            )
        );
    }

    private function getOpenListFieldWithBindType(string $bind_class): \Tracker_FormElement_Field_OpenList
    {
        $field = $this->createStub(\Tracker_FormElement_Field_OpenList::class);
        $field->method('getBind')->willReturn(
            $this->createStub($bind_class)
        );

        return $field;
    }
}
