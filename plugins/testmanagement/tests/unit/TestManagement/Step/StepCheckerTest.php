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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\Step;

use Luracast\Restler\RestException;
use Tracker_Artifact_ChangesetValue_Text;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StepCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsAnExceptionWhenTheStepDoesNotHaveDescription(): void
    {
        $step =
            [
                'expected_results'        => 'some results',
                'expected_results_format' => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ];

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        self::expectExceptionMessage('Description or Expected Result text field missing');
        StepChecker::checkStepDataFromRESTPost($step);
    }

    public function testItThrowsAnExceptionWhenTheStepDoesNotHaveExpectedResult(): void
    {
        $step =
            [
                'description'        => 'some description',
                'description_format' => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ];

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        self::expectExceptionMessage('Description or Expected Result text field missing');

        StepChecker::checkStepDataFromRESTPost($step);
    }

    public function testItThrowsAnExceptionWhenDescriptionOfTheStepHasAnInvalidFormat(): void
    {
        $step =
            [
                'description'             => 'some description',
                'description_format'      => 'vroom_format',
                'expected_results'        => 'some results',
                'expected_results_format' => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ];

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        self::expectExceptionMessage("Invalid format given, only 'html', 'text' or 'commonmark' are supported for step");

        StepChecker::checkStepDataFromRESTPost($step);
    }

    public function testItThrowsAnExceptionWhenExpectedResultOfTheStepHasAnInvalidFormat(): void
    {
        $step =
            [
                'description'             => 'some description',
                'description_format'      =>  Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                'expected_results'        => 'some results',
                'expected_results_format' => 'honk_honk_format',
            ];

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        self::expectExceptionMessage("Invalid format given, only 'html', 'text' or 'commonmark' are supported for step");

        StepChecker::checkStepDataFromRESTPost($step);
    }

    public function testItThrowsAnExceptionWhenExpectedResultFormatIsNotSet(): void
    {
        $step =
            [
                'description'             => 'some description',
                'expected_results'        => 'some results',
                'expected_results_format' => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
            ];

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        self::expectExceptionMessage('Description format or Expected Result format is missing');

        StepChecker::checkStepDataFromRESTPost($step);
    }

    public function testItThrowsAnExceptionWhenDescriptionFormatIsNotSet(): void
    {
        $step =
            [
                'description'             => 'some description',
                'description_format'      =>  Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                'expected_results'        => 'some results',
            ];


        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        self::expectExceptionMessage('Description format or Expected Result format is missing');

        StepChecker::checkStepDataFromRESTPost($step);
    }

    public function testItReturnsTrueWhenTheProvidedFormatIsValid(): void
    {
        self::assertTrue(StepChecker::isSubmittedFormatValid('text'));
        self::assertTrue(StepChecker::isSubmittedFormatValid('html'));
        self::assertTrue(StepChecker::isSubmittedFormatValid('commonmark'));
    }

    public function testItReturnsFalseWhenTheProvidedFormatIsNotValid(): void
    {
        self::assertFalse(StepChecker::isSubmittedFormatValid('wololo'));
        self::assertFalse(StepChecker::isSubmittedFormatValid('format'));
        self::assertFalse(StepChecker::isSubmittedFormatValid('oh!'));
    }
}
