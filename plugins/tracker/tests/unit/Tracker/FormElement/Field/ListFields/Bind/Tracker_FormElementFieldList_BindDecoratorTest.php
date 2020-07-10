<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field_List_BindDecorator;

final class Tracker_FormElementFieldList_BindDecoratorTest extends TestCase // @codingStandardsIgnoreLine
{
    public function testIsUsingOldPaletteWithLegacyColorComingFromXMLImport(): void
    {
        $decorator = new Tracker_FormElement_Field_List_BindDecorator(101, 1, 255, 255, 255, '');
        $this->assertTrue($decorator->isUsingOldPalette());
    }

    public function testIsUsingOldPaletteWithNoColorComingFromXMLImport(): void
    {
        $decorator = new Tracker_FormElement_Field_List_BindDecorator(101, 1, 0, 0, 0, 'inca-silver');
        $this->assertFalse($decorator->isUsingOldPalette());
    }
}
