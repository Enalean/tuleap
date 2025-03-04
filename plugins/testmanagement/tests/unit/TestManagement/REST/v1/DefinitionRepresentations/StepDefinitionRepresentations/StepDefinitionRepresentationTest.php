<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see < http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations\StepDefinitionRepresentations;


#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class StepDefinitionRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testAddTheCommonmarkDescriptionInRepresentationIfNotNull(): void
    {
        $representation = new StepDefinitionRepresentation(
            1,
            'description text',
            'html',
            '**description text**',
            'expected result text',
            'html',
            null,
            1
        );

        $json_representation = $representation->jsonSerialize();
        $this->assertEquals('**description text**', $json_representation['commonmark_description']);
        $this->assertArrayNotHasKey('commonmark_expected_results', $json_representation);
    }

    public function testAddTheCommonmarkExpectedResultsInRepresentationIfNotNull(): void
    {
        $representation = new StepDefinitionRepresentation(
            1,
            'description text',
            'html',
            null,
            'expected result text',
            'html',
            '_expected result text_',
            1
        );

        $json_representation = $representation->jsonSerialize();
        $this->assertEquals('_expected result text_', $json_representation['commonmark_expected_results']);
        $this->assertArrayNotHasKey('commonmark_description', $json_representation);
    }

    public function testAddTheCommonmarkKeysIfNotNull(): void
    {
        $representation = new StepDefinitionRepresentation(
            1,
            'description text',
            'html',
            '**description text**',
            'expected result text',
            'html',
            '_expected result text_',
            1
        );

        $json_representation = $representation->jsonSerialize();
        $this->assertEquals('_expected result text_', $json_representation['commonmark_expected_results']);
        $this->assertEquals('**description text**', $json_representation['commonmark_description']);
    }

    public function testThereIsNoCommonmarkKeyIfNull(): void
    {
        $representation = new StepDefinitionRepresentation(
            1,
            'description text',
            'html',
            null,
            'expected result text',
            'html',
            null,
            1
        );

        $json_representation = $representation->jsonSerialize();
        $this->assertArrayNotHasKey('commonmark_expected_results', $json_representation);
        $this->assertArrayNotHasKey('commonmark_description', $json_representation);
    }
}
