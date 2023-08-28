<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\XML;

class PHPCastTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItTransformsZeroToFalse(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><stuff enabled="0" />');

        self::assertNotNull($xml['enabled']);
        self::assertFalse(PHPCast::toBoolean($xml['enabled']));
    }

    public function testItTransformsOneToTrue(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><stuff enabled="1" />');

        self::assertNotNull($xml['enabled']);
        self::assertTrue(PHPCast::toBoolean($xml['enabled']));
    }

    public function testItTransformsTrueToTrue(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><stuff enabled="true" />');

        self::assertNotNull($xml['enabled']);
        self::assertTrue(PHPCast::toBoolean($xml['enabled']));
    }

    public function testItTransformsFalseToFalse(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><stuff enabled="false" />');

        self::assertNotNull($xml['enabled']);
        self::assertFalse(PHPCast::toBoolean($xml['enabled']));
    }

    public function testItTransformsGarbageToFalse(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><stuff enabled="stuff" />');

        self::assertNotNull($xml['enabled']);
        self::assertFalse(PHPCast::toBoolean($xml['enabled']));
    }
}
