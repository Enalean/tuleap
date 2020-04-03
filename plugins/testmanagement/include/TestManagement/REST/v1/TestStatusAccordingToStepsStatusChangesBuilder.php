<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class TestStatusAccordingToStepsStatusChangesBuilder
{
    /**
     * @param array                          $changes
     * @param Step[]                         $steps_defined_in_test
     * @param array                          $steps_changes
     *
     */
    public function enforceTestStatusAccordingToStepsStatus(
        Tracker_FormElement_Field_List $status_field,
        array &$changes,
        array $steps_defined_in_test,
        array $steps_changes
    ): void {
        if (empty($steps_defined_in_test)) {
            return;
        }

        if (empty($steps_changes)) {
            return;
        }

        $status     = $this->deductStatusFromSteps($steps_defined_in_test, $steps_changes);
        $values_ids = $this->getValuesIdsIndexedByLabel($status_field);

        $value_representation                 = new ArtifactValuesRepresentation();
        $value_representation->field_id       = (int) $status_field->getId();
        $value_representation->bind_value_ids = [$values_ids[$status]];

        $changes[] = $value_representation;
    }

    private function getValuesIdsIndexedByLabel(Tracker_FormElement_Field_List $status_field): array
    {
        $bind = $status_field->getBind();
        if (! $bind) {
            return [];
        }
        return array_reduce(
            $bind->getAllValues(),
            function (array $carry, Tracker_FormElement_Field_List_BindValue $value) {
                $carry[$value->getLabel()] = $value->getId();

                return $carry;
            },
            []
        );
    }

    /**
     * @param array $steps_defined_in_test
     * @param array $steps_changes
     *
     * @return mixed|string
     */
    private function deductStatusFromSteps(array $steps_defined_in_test, array $steps_changes)
    {
        $nb = $this->countStatus($steps_defined_in_test, $steps_changes);

        if ($nb['failed'] > 0) {
            return 'failed';
        }
        if ($nb['blocked'] > 0) {
            return 'blocked';
        }
        if ($nb['notrun'] > 0) {
            return 'notrun';
        }

        return 'passed';
    }

    /**
     * @return int[]
     *
     * @psalm-return array{passed: int, failed: int, blocked: int, notrun: int}
     */
    private function countStatus(
        array $steps_defined_in_test,
        array $steps_changes
    ): array {
        $nb = [
            'passed'  => 0,
            'failed'  => 0,
            'blocked' => 0,
            'notrun'  => 0
        ];
        foreach ($steps_defined_in_test as $step) {
            $status = 'notrun';
            if (isset($steps_changes[$step->getId()])) {
                $status = $steps_changes[$step->getId()];
            }

            $nb[$status]++;
        }

        return $nb;
    }
}
