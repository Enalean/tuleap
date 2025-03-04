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
class XMLPreferenceTest extends TestCase
{
    public function testItExportsNothingIfNoValues(): void
    {
        $preference = new XMLPreference('note');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><widget/>');

        $preference->export($xml);

        self::assertCount(0, $xml->preference);
    }

    public function testItExportsPreferenceValues(): void
    {
        $preference = (new XMLPreference('note'))
            ->withValue(XMLPreferenceValue::ref('id', 'F17'))
            ->withValue(XMLPreferenceValue::ref('another-id', 'F16'));

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><widget/>');

        $preference->export($xml);

        self::assertCount(1, $xml->preference);
        self::assertEquals('note', (string) $xml->preference['name']);
        self::assertCount(2, $xml->preference->reference);
    }
}
