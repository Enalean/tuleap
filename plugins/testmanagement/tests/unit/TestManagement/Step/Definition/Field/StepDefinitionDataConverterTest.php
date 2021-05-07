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

namespace Tuleap\TestManagement\Step\Definition\Field;

use Tracker_Artifact_ChangesetValue_Text;

final class StepDefinitionDataConverterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItConvertsStepDefinitionRESTFormatToDBCompatibleFormat(): void
    {
        $steps = [
            [
                "description"             => "some description",
                "description_format"      => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                "expected_results"        => "somme results",
                "expected_results_format" => Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT
            ],
            [
                "description"             => "description step 2",
                "description_format"      => Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT,
                "expected_results"        => "somme results of step 2",
                "expected_results_format" => Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
            ],
        ];

        $expected_converted_step = [
            "description"             => ["some description", "description step 2"],
            "description_format"      => [
                Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
            ],
            "expected_results"        => ["somme results", "somme results of step 2"],
            "expected_results_format" => [
                Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT,
                Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
            ]
        ];

        self::assertEquals(
            $expected_converted_step,
            StepDefinitionDataConverter::convertStepDefinitionFromRESTPostFormatToDBCompatibleFormat($steps)
        );
    }
}
