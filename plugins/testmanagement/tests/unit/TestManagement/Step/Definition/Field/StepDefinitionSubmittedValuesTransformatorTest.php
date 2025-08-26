<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\Step\Definition\Field;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\Files\FileURLSubstitutor;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StepDefinitionSubmittedValuesTransformatorTest extends TestCase
{
    public function testItTransformsSubmittedValuesIntoStepsObjects(): void
    {
        $submitted_values = [
            'id' => [
                '1',
                '2',
                '3',
                '4',
                '5',
            ],
            'description_format' => [
                'text',
                'html',
                'text',
                'html',
                'commonmark',
            ],
            'description' => [
                '01',
                '<p><strong>04</strong></p>',
                'This is text',
                '<p>This is <strong>HTML</strong></p>',
                '**05**',
            ],
            'expected_results_format'  => [
                'text',
                'html',
                'text',
                'html',
                'commonmark',
            ],
            'expected_results' => [
                '01',
                '<p><em>04</em></p>',
                'text

                with

                newlines',
                '<p>HTML</p>



                <p><strong>with</strong></p>



                <p>newlines</p>

                ',
                '--05--',
            ],
        ];

        $url_mapping = new CreatedFileURLMapping();

        $steps = (new StepDefinitionSubmittedValuesTransformator(new FileURLSubstitutor()))
            ->transformSubmittedValuesIntoArrayOfStructuredSteps($submitted_values, $url_mapping);

        self::assertCount(5, $steps);

        self::assertSame('text', $steps[0]->getDescriptionFormat());
        self::assertSame('01', $steps[0]->getDescription());
        self::assertSame('text', $steps[0]->getExpectedResultsFormat());
        self::assertSame('01', $steps[0]->getExpectedResults());

        self::assertSame('html', $steps[1]->getDescriptionFormat());
        self::assertSame('<p><strong>04</strong></p>', $steps[1]->getDescription());
        self::assertSame('html', $steps[1]->getExpectedResultsFormat());
        self::assertSame('<p><em>04</em></p>', $steps[1]->getExpectedResults());

        self::assertSame('text', $steps[2]->getDescriptionFormat());
        self::assertSame('This is text', $steps[2]->getDescription());
        self::assertSame('text', $steps[2]->getExpectedResultsFormat());
        self::assertSame(
            'text

                with

                newlines',
            $steps[2]->getExpectedResults()
        );

        self::assertSame('html', $steps[3]->getDescriptionFormat());
        self::assertSame('<p>This is <strong>HTML</strong></p>', $steps[3]->getDescription());
        self::assertSame('html', $steps[3]->getExpectedResultsFormat());
        self::assertSame(
            '<p>HTML</p>



                <p><strong>with</strong></p>



                <p>newlines</p>',
            $steps[3]->getExpectedResults()
        );

        self::assertSame('commonmark', $steps[4]->getDescriptionFormat());
        self::assertSame('**05**', $steps[4]->getDescription());
        self::assertSame('commonmark', $steps[4]->getExpectedResultsFormat());
        self::assertSame('--05--', $steps[4]->getExpectedResults());
    }

    public function testItTransformsSubmittedValuesIntoEmptyStepsObjectIfAllAreRemoved(): void
    {
        $submitted_values = [
            'no_steps' => true,
        ];

        $url_mapping = new CreatedFileURLMapping();

        $steps = (new StepDefinitionSubmittedValuesTransformator(new FileURLSubstitutor()))
            ->transformSubmittedValuesIntoArrayOfStructuredSteps($submitted_values, $url_mapping);

        self::assertEmpty($steps);
    }

    public function testItTransformsSubmittedValuesIntoEmptyStepsObjectIfDescriptionKeyIsMissing(): void
    {
        $submitted_values = [
            'id' => [
                '1',
                '2',
                '3',
                '4',
                '5',
            ],
            'description_format' => [
                'text',
                'html',
                'text',
                'html',
                'commonmark',
            ],
            'expected_results_format'  => [
                'text',
                'html',
                'text',
                'html',
                'commonmark',
            ],
            'expected_results' => [
                '01',
                '<p><em>04</em></p>',
                'text

                with

                newlines',
                '<p>HTML</p>



                <p><strong>with</strong></p>



                <p>newlines</p>

                ',
                '--05--',
            ],
        ];

        $url_mapping = new CreatedFileURLMapping();

        $steps = (new StepDefinitionSubmittedValuesTransformator(new FileURLSubstitutor()))
            ->transformSubmittedValuesIntoArrayOfStructuredSteps($submitted_values, $url_mapping);

        self::assertEmpty($steps);
    }
}
