<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Step\Definition\Field;

use Tracker_Artifact_ChangesetValue_Text;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\FormElement\Field\Files\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\Files\FileURLSubstitutor;

class StepDefinitionSubmittedValuesTransformator
{
    public function __construct(private FileURLSubstitutor $substitutor)
    {
    }

    /**
     * @return Step[]
     *
     * @psalm-return list<Step>
     */
    public function transformSubmittedValuesIntoArrayOfStructuredSteps(
        array $submitted_values,
        CreatedFileURLMapping $url_mapping,
    ): array {
        if ($this->doesUserWantToRemoveAllSteps($submitted_values) || ! isset($submitted_values['description'])) {
            return [];
        }

        $steps = [];
        $rank  = StepsDefinition::START_RANK;
        foreach ($submitted_values['description'] as $key => $description) {
            $description = trim($description);
            if (! $description) {
                continue;
            }
            if (! isset($submitted_values['description_format'][$key])) {
                continue;
            }
            $description_format = $submitted_values['description_format'][$key];

            $expected_results = '';
            if (isset($submitted_values['expected_results'][$key])) {
                $expected_results = trim($submitted_values['expected_results'][$key]);
            }
            $expected_results_format = '';
            if (isset($submitted_values['expected_results_format'][$key])) {
                $expected_results_format = $submitted_values['expected_results_format'][$key];
            }

            if ($description_format === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
                $description = $this->substitutor->substituteURLsInHTML($description, $url_mapping);
            }

            if ($expected_results_format === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
                $expected_results = $this->substitutor->substituteURLsInHTML($expected_results, $url_mapping);
            }

            $steps[] = new Step(
                0,
                $description,
                $description_format,
                $expected_results,
                $expected_results_format,
                $rank
            );
        }

        return $steps;
    }

    private function doesUserWantToRemoveAllSteps(array $value): bool
    {
        return isset($value['no_steps']) && $value['no_steps'];
    }
}
