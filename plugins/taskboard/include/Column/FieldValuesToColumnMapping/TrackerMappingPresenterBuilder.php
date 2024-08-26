<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use Cardwall_Column;
use Planning_Milestone;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Taskboard\Tracker\TrackerCollectionRetriever;

class TrackerMappingPresenterBuilder
{
    public function __construct(
        private TrackerCollectionRetriever $trackers_retriever,
        private MappedFieldRetriever $mapped_field_retriever,
        private MappedValuesRetriever $mapped_values_retriever,
    ) {
    }

    /**
     * @return TrackerMappingPresenter[]
     */
    public function buildMappings(Planning_Milestone $milestone, Cardwall_Column $column): array
    {
        return $this->trackers_retriever->getTrackersForMilestone($milestone)->map(
            function (TaskboardTracker $tracker) use ($column) {
                return $this->buildMappingForATracker($tracker, $column);
            }
        );
    }

    private function buildMappingForATracker(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
    ): TrackerMappingPresenter {
        $field_id = $this->mapped_field_retriever->getField($taskboard_tracker)
            ->mapOr(static fn($field) => $field->getId(), null);

        $value_mapping_presenters = $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $column)
            ->mapOr(static function ($mapped_values) {
                $presenters = [];
                foreach ($mapped_values->getValueIds() as $value_id) {
                    $presenters[] = new ListFieldValuePresenter((int) $value_id);
                }
                return $presenters;
            }, []);

        return new TrackerMappingPresenter($taskboard_tracker->getTrackerId(), $field_id, $value_mapping_presenters);
    }
}
