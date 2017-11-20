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

class QueryParameterParserTest extends \TuleapTestCase
{
    /** @var QueryParameterParser */
    private $query_parser;

    public function setUp()
    {
        parent::setUp();
        $this->query_parser = new QueryParameterParser(
            new JsonDecoder()
        );
    }

    public function itThrowsWhenLabelsIdIsMissing()
    {
        $this->expectException('Tuleap\\REST\\MissingMandatoryParameterException');

        $this->query_parser->getArrayOfInt('{"some_other_property": ""}', 'labels_id');
    }

    public function itThrowsWhenLabelsIdIsNotAnArray()
    {
        $this->expectException('Tuleap\\REST\\InvalidParameterTypeException');

        $this->query_parser->getArrayOfInt('{"labels_id": ""}', 'labels_id');
    }

    public function itThrowsWhenLabelsIdIsNotAnArrayOfInt()
    {
        $this->expectException('Tuleap\\REST\\InvalidParameterTypeException');

        $this->query_parser->getArrayOfInt('{"labels_id": ["a", "b"]}', 'labels_id');
    }

    public function itThrowsWhenLabelsIdAreDuplicated()
    {
        $this->expectException('Tuleap\\REST\\DuplicatedParameterValueException');

        $this->query_parser->getArrayOfInt('{"labels_id": [1, 1, 2, 2]}', 'labels_id');
    }

    public function itReturnsAnArrayOfLabelsId()
    {
        $result = $this->query_parser->getArrayOfInt('{"labels_id": [21, 74]}', 'labels_id');

        $this->assertEqual(array(21, 74), $result);
    }

    public function itThrowsWhenTrackerReportIdIsMissing()
    {
        $this->expectException('Tuleap\\REST\\MissingMandatoryParameterException');

        $this->query_parser->getInt('{"some_other_property": ""}', 'tracker_report_id');
    }

    public function itThrowsWhenTrackerReportIdIsNotAnInt()
    {
        $this->expectException('Tuleap\\REST\\InvalidParameterTypeException');

        $this->query_parser->getInt('{"tracker_report_id": "a"}', 'tracker_report_id');
    }

    public function itReturnsAnIntForTrackerReportId()
    {
        $result = $this->query_parser->getInt('{"tracker_report_id": 47}', 'tracker_report_id');

        $this->assertEqual(47, $result);
    }
}
