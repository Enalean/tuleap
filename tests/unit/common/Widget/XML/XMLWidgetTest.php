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

namespace Tuleap\Widget\XML;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class XMLWidgetTest extends TestCase
{
    public function testExportBasicWidget(): void
    {
        $widget = new XMLWidget('projectmembers');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><column/>');

        $widget->export($xml);

        self::assertCount(1, $xml->widget);
        self::assertEquals('projectmembers', $xml->widget['name']);
        self::assertCount(0, $xml->widget->preference);
    }

    public function testExportPreferences(): void
    {
        $widget = (new XMLWidget('projectmembers'))->withPreference((new XMLPreference('lorem'))->withValue(XMLPreferenceValue::ref('id', 'F17')));

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><column/>');

        $widget->export($xml);

        self::assertCount(1, $xml->widget);
        self::assertEquals(
            '<preference name="lorem"><reference name="id" REF="F17"/></preference>',
            $xml->widget->preference->asXML()
        );
    }
}
