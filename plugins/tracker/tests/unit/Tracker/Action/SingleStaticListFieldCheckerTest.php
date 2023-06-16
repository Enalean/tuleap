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

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Action\SingleStaticListFieldChecker;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;

final class SingleStaticListFieldCheckerTest extends TestCase
{
    private SingleStaticListFieldChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new SingleStaticListFieldChecker();
    }

    public function testItIsNotASingleValueListWhenFieldTypeIsNotAList(): void
    {
        $field = TrackerFormElementStringFieldBuilder::aStringField(10)->build();

        self::assertFalse($this->checker->isSingleValueStaticListField($field));
    }

    public function testItIsNotASingleValueWhenListCanHaveMultipleChoices(): void
    {
        $field = $this->createStub(\Tracker_FormElement_Field_List::class);
        $field->method('isMultiple')->willReturn(true);

        self::assertFalse($this->checker->isSingleValueStaticListField($field));
    }

    public function testItIsNotASingleValueWhenBindIsNotStatic(): void
    {
        $field_bind = $this->createStub(\Tracker_FormElement_Field_List_Bind_Users::class);
        $field_bind->method('getType')->willReturn(\Tracker_FormElement_Field_List_Bind_Users::TYPE);

        $field = $this->createStub(\Tracker_FormElement_Field_List::class);
        $field->method('getBind')->willReturn($field_bind);
        $field->method('isMultiple')->willReturn(false);

        self::assertFalse($this->checker->isSingleValueStaticListField($field));
    }

    public function testItIsASingleListValue(): void
    {
        $field_bind = $this->createStub(\Tracker_FormElement_Field_List_Bind_Static::class);
        $field_bind->method('getType')->willReturn(\Tracker_FormElement_Field_List_Bind_Static::TYPE);

        $field = $this->createStub(\Tracker_FormElement_Field_List::class);
        $field->method('getBind')->willReturn($field_bind);
        $field->method('isMultiple')->willReturn(false);

        self::assertTrue($this->checker->isSingleValueStaticListField($field));
    }
}
