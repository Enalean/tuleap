<?php
/**
 * Copyright (c) Ericsson AB, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use PHPUnit\Framework\TestCase;

class ConfigNotificationEmailCustomSenderFormatterTest extends TestCase
{
    public const FIELD1_DATA = "123456";
    public const FIELD2_DATA = "abcdef";
    public const FORMAT_DATA = '%field1';
    public const NO_SLUGIFY  = '@ slugify.';
    public const SLUGIFY     = 'at slugify';

    /**
     * @var ConfigNotificationEmailCustomSenderFormatter
     * */
    private $formatter;

    public function setUp(): void
    {
        parent::setUp();
        $dummydata = array();
        $dummydata['field1'] = self::FIELD1_DATA;
        $dummydata['field2'] = self::FIELD2_DATA;
        $dummydata['format'] = self::FORMAT_DATA;
        $this->formatter = new ConfigNotificationEmailCustomSenderFormatter($dummydata);
    }

    public function testReturnsInputStringIfNoFormattingFound()
    {
        $input = "This String has no formatting";
        $this->assertEquals($input, $this->formatter->formatString($input));
    }

    public function testLeavesUnknownFormattingAlone()
    {
        $input = 'This formatting is %weird_and_unkown';
        $this->assertEquals('This formatting is weird and unkown', $this->formatter->formatString($input));
    }

    public function testCorrectlyFormatsKnownFields()
    {
        $input      = 'The value %field1 shoud be in $dummydata[field1]';
        $expected   = 'The value ' . self::FIELD1_DATA . ' shoud be in dummydata field1';
        $this->assertEquals($expected, $this->formatter->formatString($input));
    }

    public function testHandlesStringsWithBothCorrectAndIncorrectFormatting()
    {
        $input      = '%field1 %fie_ld1 %random %field2%notafield';
        $expected   = self::FIELD1_DATA . ' fie ld1 random ' . self::FIELD2_DATA . ' notafield';
        $this->assertEquals($expected, $this->formatter->formatString($input));
    }

    public function testDoesNotParseRecursively()
    {
        $input      = '%format';
        $expected   = self::FORMAT_DATA;
        $this->assertEquals("field1", $this->formatter->formatString($input));
    }

    public function testDoesSlugify()
    {
        $input      = self::NO_SLUGIFY;
        $expected   = self::SLUGIFY;
        $this->assertEquals($expected, $this->formatter->formatString($input));
    }
}
