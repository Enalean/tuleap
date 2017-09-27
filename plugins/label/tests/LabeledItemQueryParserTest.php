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

namespace Tuleap\Label;

use Tuleap\REST\JsonDecoder;

require_once 'bootstrap.php';

class LabeledItemQueryParserTest extends \TuleapTestCase
{
    /**
     * @var JsonDecoder
     */
    private $json_decoder;

    public function setUp()
    {
        parent::setUp();
        $this->json_decoder = new JsonDecoder();
    }

    public function itThrowsWhenLabelsIdIsMissing()
    {
        $query_parser = $this->instantiateQueryParser();

        $this->expectException('Tuleap\\Label\\Exceptions\\MissingMandatoryParameterException');

        $query_parser->getLabelIdsFromRoute('{"some_other_property": ""}');
    }

    public function itThrowsWhenLabelsIdIsNotAnArray()
    {
        $query_parser = $this->instantiateQueryParser();

        $this->expectException('Tuleap\\Label\\Exceptions\\InvalidParameterTypeException');

        $query_parser->getLabelIdsFromRoute('{"labels_id": ""}');
    }

    public function itThrowsWhenLabelsIdIsAnEmptyArray()
    {
        $query_parser = $this->instantiateQueryParser();

        $this->expectException('Tuleap\\Label\\Exceptions\\EmptyParameterException');

        $query_parser->getLabelIdsFromRoute('{"labels_id": []}');
    }

    public function itThrowsWhenLabelsIdIsNotAnArrayOfInt()
    {
        $query_parser = $this->instantiateQueryParser();

        $this->expectException('Tuleap\\Label\\Exceptions\\InvalidParameterTypeException');

        $query_parser->getLabelIdsFromRoute('{"labels_id": ["a", "b"]}');
    }

    public function itThrowsWhenLabelsIdAreDuplicated()
    {
        $query_parser = $this->instantiateQueryParser();

        $this->expectException('Tuleap\\Label\\Exceptions\\DuplicatedParameterValueException');

        $query_parser->getLabelIdsFromRoute('{"labels_id": [1, 1, 2, 2]}');
    }

    public function itReturnsAnArrayOfLabelsId()
    {
        $query_parser = $this->instantiateQueryParser();

        $result = $query_parser->getLabelIdsFromRoute('{"labels_id": [21, 74]}');

        $this->assertEqual(array(21, 74), $result);
    }

    private function instantiateQueryParser()
    {
        return new LabeledItemQueryParser(
            $this->json_decoder
        );
    }
}
