<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\REST;

class QueryParameterParserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var QueryParameterParser */
    private $query_parser;

    protected function setUp(): void
    {
        $this->query_parser = new QueryParameterParser(
            new JsonDecoder()
        );
    }

    public function testMissingParameter(): void
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getArrayOfInt('{"some_other_property": ""}', 'labels_id');
    }

    public function testArrayOfIntWithAnEmptyString(): void
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getArrayOfInt('{"labels_id": ""}', 'labels_id');
    }

    public function testArrayOfIntWithAnArrayOfStrings(): void
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getArrayOfInt('{"labels_id": ["a", "b"]}', 'labels_id');
    }

    public function testArrayOfIntWithAnArrayContainingDuplicates(): void
    {
        $this->expectException(DuplicatedParameterValueException::class);

        $this->query_parser->getArrayOfInt('{"labels_id": [1, 1, 2, 2]}', 'labels_id');
    }

    public function testArrayOfIntReturn(): void
    {
        $result = $this->query_parser->getArrayOfInt('{"labels_id": [21, 74]}', 'labels_id');

        self::assertEquals([21, 74], $result);
    }

    public function testGetIntWithAnEmptyString(): void
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getInt('{"some_other_property": ""}', 'tracker_report_id');
    }

    public function testGetIntWithAString(): void
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getInt('{"tracker_report_id": "a"}', 'tracker_report_id');
    }

    public function testGetInt(): void
    {
        $result = $this->query_parser->getInt('{"tracker_report_id": 47}', 'tracker_report_id');

        self::assertEquals(47, $result);
    }

    public function testGetStringWithAnEmptyString(): void
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getString('{"some_other_property": ""}', 'identifier');
    }

    public function testGetStringWithAnArray(): void
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getString('{"identifier": ["test"]}', 'identifier');
    }

    public function testGetStringWithANumber(): void
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getString('{"identifier": 123}', 'identifier');
    }

    public function testGetString(): void
    {
        $result = $this->query_parser->getString('{"identifier": "test"}', 'identifier');
        self::assertEquals('test', $result);
    }

    public function testGetBoolean(): void
    {
        $result = $this->query_parser->getBoolean('{"identifier": true}', 'identifier');
        self::assertTrue($result);
    }

    public function testGetObjectWithAnEmptyString()
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getObject('{"some_other_property": ""}', 'identifier');
    }

    public function testGetObjectWithAString(): void
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getObject('{"some_other_property": "a"}', 'identifier');
    }

    public function testGetObjectWithANumber(): void
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getObject('{"identifier": 123}', 'identifier');
    }

    public function testGetObject(): void
    {
        $result = $this->query_parser->getObject('{"identifier": {"key": "value"}}', 'identifier');
        self::assertEquals(["key" => "value"], $result);
    }
}
