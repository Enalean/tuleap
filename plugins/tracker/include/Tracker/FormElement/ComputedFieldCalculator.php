<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Tracker_FormElement_Field_ComputedDao;

class ComputedFieldCalculator
{
    /**
     * @var Tracker_FormElement_Field_ComputedDao
     */
    private $computed_dao;

    public function __construct(Tracker_FormElement_Field_ComputedDao $computed_dao)
    {
        $this->computed_dao = $computed_dao;
    }

    public function calculate(
        array $artifact_ids_to_fetch,
        $timestamp,
        $stop_on_manual_value,
        $target_field_name,
        $computed_field_id
    ) {
        $sum          = null;
        $already_seen = array();

        do {
            if ($timestamp !== null) {
                $dar = $this->computed_dao->getFieldValuesAtTimestamp(
                    $artifact_ids_to_fetch,
                    $target_field_name,
                    $timestamp,
                    $computed_field_id
                );
            } else {
                $dar = $this->computed_dao->getComputedFieldValues(
                    $artifact_ids_to_fetch,
                    $target_field_name,
                    $computed_field_id,
                    $stop_on_manual_value
                );
            }

            $current_fetch_artifact = $artifact_ids_to_fetch;
            $artifact_ids_to_fetch  = array();
            $last_id                = null;
            if ($dar) {
                foreach ($dar as $row) {
                    if (! isset($already_seen[$row['id']]) && $last_id != $row['parent_id']) {
                        if (isset($row['value']) && $row['value'] !== null) {
                            $already_seen[$row['parent_id']] = true;
                            $last_id                         = $row['parent_id'];
                            $sum += $row['value'];
                        } elseif ($row['type'] == 'computed') {
                            $artifact_ids_to_fetch[] = $row['id'];
                        } elseif (isset($row[$row['type'] . '_value'])) {
                            $already_seen[$row['id']] = true;
                            $sum += $row[$row['type'] . '_value'];
                        }
                    }
                }
                $dar->freeMemory();
            }

            foreach ($current_fetch_artifact as $artifact_fetched) {
                $already_seen[$artifact_fetched] = true;
            }
        } while (count($artifact_ids_to_fetch) > 0);

        return $sum;
    }
}
