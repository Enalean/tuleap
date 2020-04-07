<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class QueryParameterParserTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var QueryParameterParser */
    private $query_parser;

    protected function setUp(): void
    {
        $this->query_parser = new QueryParameterParser(
            new JsonDecoder()
        );
    }

    public function testMissingParameter()
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getArrayOfInt('{"some_other_property": ""}', 'labels_id');
    }

    public function testArrayOfIntWithAnEmptyString()
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getArrayOfInt('{"labels_id": ""}', 'labels_id');
    }

    public function testArrayOfIntWithAnArrayOfStrings()
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getArrayOfInt('{"labels_id": ["a", "b"]}', 'labels_id');
    }

    public function testArrayOfIntWithAnArrayContainingDuplicates()
    {
        $this->expectException(DuplicatedParameterValueException::class);

        $this->query_parser->getArrayOfInt('{"labels_id": [1, 1, 2, 2]}', 'labels_id');
    }

    public function testArrayOfIntReturn()
    {
        $result = $this->query_parser->getArrayOfInt('{"labels_id": [21, 74]}', 'labels_id');

        $this->assertEquals(array(21, 74), $result);
    }

    public function testGetIntWithAnEmptyString()
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getInt('{"some_other_property": ""}', 'tracker_report_id');
    }

    public function testGetIntWithAString()
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getInt('{"tracker_report_id": "a"}', 'tracker_report_id');
    }

    public function testGetInt()
    {
        $result = $this->query_parser->getInt('{"tracker_report_id": 47}', 'tracker_report_id');

        $this->assertEquals(47, $result);
    }

    public function testGetStringWithAnEmptyString()
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getString('{"some_other_property": ""}', 'identifier');
    }

    public function testGetStringWithAnArray()
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getString('{"identifier": ["test"]}', 'identifier');
    }

    public function testGetStringWithANumber()
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getString('{"identifier": 123}', 'identifier');
    }

    public function testGetString()
    {
        $result = $this->query_parser->getString('{"identifier": "test"}', 'identifier');
        $this->assertEquals('test', $result);
    }

    public function testGetBoolean()
    {
        $result = $this->query_parser->getBoolean('{"identifier": true}', 'identifier');
        $this->assertTrue($result);
    }

    public function testGetObjectWithAnEmptyString()
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getObject('{"some_other_property": ""}', 'identifier');
    }

    public function testGetObjectWithAString()
    {
        $this->expectException(MissingMandatoryParameterException::class);

        $this->query_parser->getObject('{"some_other_property": "a"}', 'identifier');
    }

    public function testGetObjectWithANumber()
    {
        $this->expectException(InvalidParameterTypeException::class);

        $this->query_parser->getObject('{"identifier": 123}', 'identifier');
    }

    public function testGetObject()
    {
        $result = $this->query_parser->getObject('{"identifier": {"key": "value"}}', 'identifier');
        $this->assertEquals(["key" => "value"], $result);
    }
}
