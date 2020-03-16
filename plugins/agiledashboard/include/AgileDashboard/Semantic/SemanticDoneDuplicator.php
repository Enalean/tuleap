<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Semantic;

use Tracker_Semantic_StatusDao;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;

class SemanticDoneDuplicator
{
    /**
     * @var SemanticDoneDao
     */
    private $semantic_done_dao;

    /**
     * @var Tracker_Semantic_StatusDao
     */
    private $semantic_status_dao;

    public function __construct(SemanticDoneDao $semantic_done_dao, Tracker_Semantic_StatusDao $semantic_status_dao)
    {
        $this->semantic_done_dao   = $semantic_done_dao;
        $this->semantic_status_dao = $semantic_status_dao;
    }

    public function duplicate(int $from_tracker_id, int $to_tracker_id, array $field_mapping)
    {
        $result = $this->semantic_done_dao->getSelectedValues($from_tracker_id);
        if ($result->count() === 0) {
            return;
        }

        $from_semantic_status_field_row = $this->semantic_status_dao->searchByTrackerId($from_tracker_id)->getRow();
        if (! $from_semantic_status_field_row) {
            return;
        }

        $from_semantic_status_field_id = (int) $from_semantic_status_field_row['field_id'];
        $values_mapping = $this->extractValueMapping(
            $field_mapping,
            $from_semantic_status_field_id
        );

        $to_selected_value_ids = [];
        foreach ($result as $row) {
            $from_selected_value_id = (int) $row['value_id'];
            if (isset($values_mapping[$from_selected_value_id])) {
                $to_selected_value_ids[] = (int) $values_mapping[$from_selected_value_id];
            }
        }

        if (count($to_selected_value_ids) > 0) {
            $this->semantic_done_dao->addForTracker($to_tracker_id, $to_selected_value_ids);
        }
    }

    private function extractValueMapping(array $field_mapping, int $from_semantic_status_field_id): array
    {
        $values_mapping = [];
        foreach ($field_mapping as $mapping) {
            if ((int) $mapping['from'] === (int) $from_semantic_status_field_id) {
                $values_mapping = $mapping['values'];
            }
        }
        return $values_mapping;
    }
}
