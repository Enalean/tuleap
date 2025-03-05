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
class XMLPreferenceValueTest extends TestCase
{
    public function testExportRefValue(): void
    {
        $value = XMLPreferenceValue::ref('id', 'F17');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><preference/>');

        $value->export($xml);

        self::assertEquals(
            '<reference name="id" REF="F17"/>',
            $xml->reference->asXML()
        );
    }

    public function testExportTextValue(): void
    {
        $value = XMLPreferenceValue::text('title', 'Note from the Tuleap team');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><preference/>');

        $value->export($xml);

        self::assertEquals(
            '<value name="title"><![CDATA[Note from the Tuleap team]]></value>',
            $xml->value->asXML()
        );
    }
}
