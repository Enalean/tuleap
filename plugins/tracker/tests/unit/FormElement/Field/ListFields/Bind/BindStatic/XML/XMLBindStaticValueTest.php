<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class XMLBindStaticValueTest extends TestCase
{
    public function testItExportASimpleValue(): void
    {
        $bind = simplexml_load_string(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <bind>
                <items />
            </bind>
            EOS
        );

        $value = XMLBindStaticValue::fromLabel(
            new XMLSelectBoxField('F1', 'status'),
            'Open'
        );

        $value->export($bind, $bind->items);

        self::assertEquals(
            '<item ID="VF1_open" label="Open"/>',
            $bind->items->item[0]->asXML()
        );
    }

    public function testItExportAValueWithADescription(): void
    {
        $bind = simplexml_load_string(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <bind>
                <items />
            </bind>
            EOS
        );

        $value = XMLBindStaticValue::fromLabel(
            new XMLSelectBoxField('F1', 'status'),
            'Open'
        )->withDescription('Lorem ipsum');

        $value->export($bind, $bind->items);

        self::assertEquals('Lorem ipsum', (string) $bind->items->item[0]->description);
    }

    public function testItExportAValueWithADecorator(): void
    {
        $bind = simplexml_load_string(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <bind>
                <items />
            </bind>
            EOS
        );

        $value = XMLBindStaticValue::fromLabel(
            new XMLSelectBoxField('F1', 'status'),
            'Open'
        )->withDecorator('fiesta-red');

        $value->export($bind, $bind->items);

        self::assertEquals(
            '<decorator REF="VF1_open" tlp_color_name="fiesta-red"/>',
            $bind->decorators->decorator[0]->asXML()
        );
    }

    public function testItExportAValueWithADefaultValue(): void
    {
        $bind = simplexml_load_string(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <bind>
                <items />
            </bind>
            EOS
        );

        $value = XMLBindStaticValue::fromLabel(
            new XMLSelectBoxField('F1', 'status'),
            'Open'
        )->withIsDefault();

        $value->export($bind, $bind->items);

        self::assertEquals(
            '<value REF="VF1_open"/>',
            $bind->default_values->value[0]->asXML()
        );
    }

    public function testItExportAValueAndAddItToAlreadyExistingOnes(): void
    {
        $bind = simplexml_load_string(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <bind>
                <items>
                    <item ID="VF1_open" label="Open"/>
                </items>
                <decorators>
                    <decorator REF="VF1_open" tlp_color_name="fiesta-red"/>
                </decorators>
                <default_values>
                    <value REF="VF1_open"/>
                </default_values>
            </bind>
            EOS
        );

        $value = XMLBindStaticValue::fromLabel(
            new XMLSelectBoxField('F1', 'status'),
            'Closed'
        )
            ->withDescription('Lorem ipsum')
            ->withIsDefault()
            ->withDecorator('teddy-brown');

        $value->export($bind, $bind->items);

        self::assertEquals(
            <<<EOS
            <?xml version="1.0" encoding="UTF-8"?>
            <bind>
                <items>
                    <item ID="VF1_open" label="Open"/>
                <item ID="VF1_closed" label="Closed"><description><![CDATA[Lorem ipsum]]></description></item></items>
                <decorators>
                    <decorator REF="VF1_open" tlp_color_name="fiesta-red"/>
                <decorator REF="VF1_closed" tlp_color_name="teddy-brown"/></decorators>
                <default_values>
                    <value REF="VF1_open"/>
                <value REF="VF1_closed"/></default_values>
            </bind>\n
            EOS,
            $bind->asXML()
        );
    }
}
