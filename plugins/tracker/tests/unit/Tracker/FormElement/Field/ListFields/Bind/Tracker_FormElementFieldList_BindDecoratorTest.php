<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use SimpleXMLElement;
use Tracker_FormElement_Field_List_BindDecorator;

require_once __DIR__ . '/../../../../../bootstrap.php';

class Tracker_FormElementFieldList_BindDecoratorTest extends TestCase // @codingStandardsIgnoreLine
{
    public function testItExportOldPaletteColor()
    {
        $decorator = new Tracker_FormElement_Field_List_BindDecorator(101, 1, 255, 255, 255, null);
        $root      = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $decorator->exportToXml($root, 'val');

        $attr = $root->decorator->attributes();
        $this->assertEquals((string) $attr->r, 255);
        $this->assertEquals((string) $attr->g, 255);
        $this->assertEquals((string) $attr->b, 255);
    }

    public function testitExportTlpColor()
    {
        $decorator = new Tracker_FormElement_Field_List_BindDecorator(101, 1, null, null, null, 'inca-silver');
        $root      = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><project />');
        $decorator->exportToXml($root, 'val');

        $attr = $root->decorator->attributes();
        $this->assertEquals((string) $attr->tlp_color_name, 'inca-silver');
    }

    public function testIsUsingOldPaletteWithLegacyColorComingFromXMLImport()
    {
        $decorator = new Tracker_FormElement_Field_List_BindDecorator(101, 1, 255, 255, 255, '');
        $this->assertTrue($decorator->isUsingOldPalette());
    }

    public function testIsUsingOldPaletteWithNoColorComingFromXMLImport()
    {
        $decorator = new Tracker_FormElement_Field_List_BindDecorator(101, 1, 0, 0, 0, 'inca-silver');
        $this->assertFalse($decorator->isUsingOldPalette());
    }
}
