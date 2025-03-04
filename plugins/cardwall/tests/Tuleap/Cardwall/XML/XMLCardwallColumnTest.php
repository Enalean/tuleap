<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Cardwall\XML;

use SimpleXMLElement;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class XMLCardwallColumnTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItExportsColumnWithoutIdAndColours(): void
    {
        $columns_xml =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_column = (new XMLCardwallColumn('Column'))->export($columns_xml);

        self::assertSame('column', $xml_column->getName());
        self::assertEquals('Column', $xml_column['label']);
        self::assertFalse(isset($xml_column->attributes()['id']));
        self::assertFalse(isset($xml_column->attributes()['tlp_color_name']));
        self::assertFalse(isset($xml_column->attributes()['bg_red']));
        self::assertFalse(isset($xml_column->attributes()['bg_green']));
        self::assertFalse(isset($xml_column->attributes()['bg_blue']));
    }

    public function testItExportsColumnWithIdWithoutColours(): void
    {
        $columns_xml =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_column = (new XMLCardwallColumn('Column'))
            ->withId('C154')
            ->export($columns_xml);

        self::assertSame('column', $xml_column->getName());
        self::assertEquals('Column', $xml_column['label']);
        self::assertEquals('C154', $xml_column['id']);
        self::assertFalse(isset($xml_column->attributes()['tlp_color_name']));
        self::assertFalse(isset($xml_column->attributes()['bg_red']));
        self::assertFalse(isset($xml_column->attributes()['bg_green']));
        self::assertFalse(isset($xml_column->attributes()['bg_blue']));
    }

    public function testItExportsColumnWithIdAndTLPColour(): void
    {
        $columns_xml =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_column = (new XMLCardwallColumn('Column'))
            ->withId('C154')
            ->withTLPColorName('red')
            ->export($columns_xml);

        self::assertSame('column', $xml_column->getName());
        self::assertEquals('Column', $xml_column['label']);
        self::assertEquals('C154', $xml_column['id']);
        self::assertEquals('red', $xml_column['tlp_color_name']);
        self::assertFalse(isset($xml_column->attributes()['bg_red']));
        self::assertFalse(isset($xml_column->attributes()['bg_green']));
        self::assertFalse(isset($xml_column->attributes()['bg_blue']));
    }

    public function testItExportsColumnWithIdAndLegacyColour(): void
    {
        $columns_xml =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_column = (new XMLCardwallColumn('Column'))
            ->withId('C154')
            ->withLegacyColorsName('255', '255', '255')
            ->export($columns_xml);

        self::assertSame('column', $xml_column->getName());
        self::assertEquals('Column', $xml_column['label']);
        self::assertEquals('C154', $xml_column['id']);
        self::assertFalse(isset($xml_column->attributes()['tlp_color_name']));
        self::assertEquals('255', $xml_column['bg_red']);
        self::assertEquals('255', $xml_column['bg_green']);
        self::assertEquals('255', $xml_column['bg_blue']);
    }

    public function testItExportsColumnWithITLPColourIfThereIsTLPAndLegacyColour(): void
    {
        $columns_xml =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><plannings />');

        $xml_column = (new XMLCardwallColumn('Column'))
            ->withId('C154')
            ->withTLPColorName('blue')
            ->withLegacyColorsName('255', '255', '255')
            ->export($columns_xml);

        self::assertSame('column', $xml_column->getName());
        self::assertEquals('Column', $xml_column['label']);
        self::assertEquals('C154', $xml_column['id']);
        self::assertEquals('blue', $xml_column['tlp_color_name']);
        self::assertFalse(isset($xml_column->attributes()['bg_red']));
        self::assertFalse(isset($xml_column->attributes()['bg_green']));
        self::assertFalse(isset($xml_column->attributes()['bg_blue']));
    }
}
