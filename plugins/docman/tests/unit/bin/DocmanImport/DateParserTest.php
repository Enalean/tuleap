<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../../../../bin/DocmanImport/DateParser.class.php');

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class DateParserTest extends TestCase
{

    public function testParseIso8601(): void
    {
        $currentTimeStamp = time();
        $curentIsoDate = date('c', $currentTimeStamp);
        $this->assertEquals($currentTimeStamp, DateParser::parseIso8601($curentIsoDate));

        $date1 = "20001201T0154+0100";
        $date2 = "2000-12-01T02:54+0200";
        $date3 = "2000-12-01T00:54:00Z";
        $date4 = "20001201T02:54+0200";
        //$ts = 975632040;
        $ts = gmmktime(0, 54, 0, 12, 1, 2000);

        $this->assertEquals($ts, DateParser::parseIso8601($date1));
        $this->assertEquals($ts, DateParser::parseIso8601($date2));
        $this->assertEquals($ts, DateParser::parseIso8601($date3));
        $this->assertEquals($ts, DateParser::parseIso8601($date4));

        $this->assertEquals(DateParser::parseIso8601($date1), DateParser::parseIso8601($date2));
        $this->assertEquals(DateParser::parseIso8601($date1), DateParser::parseIso8601($date3));
        $this->assertEquals(DateParser::parseIso8601($date1), DateParser::parseIso8601($date4));
        $this->assertEquals(DateParser::parseIso8601($date2), DateParser::parseIso8601($date3));
        $this->assertEquals(DateParser::parseIso8601($date2), DateParser::parseIso8601($date4));
        $this->assertEquals(DateParser::parseIso8601($date3), DateParser::parseIso8601($date4));
    }
}
