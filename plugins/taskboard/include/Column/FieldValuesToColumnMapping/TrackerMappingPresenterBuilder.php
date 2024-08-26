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
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingDao;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Taskboard\Tracker\TrackerCollectionRetriever;

class TrackerMappingPresenterBuilder
{
    /** @var TrackerCollectionRetriever */
    private $trackers_retriever;
    /** @var MappedFieldRetriever */
    private $mapped_field_retriever;
    /** @var MappedValuesRetriever */
    private $mapped_values_retriever;

    public function __construct(
        TrackerCollectionRetriever $trackers_retriever,
        MappedFieldRetriever $mapped_field_retriever,
        MappedValuesRetriever $mapped_values_retriever,
    ) {
        $this->trackers_retriever      = $trackers_retriever;
        $this->mapped_field_retriever  = $mapped_field_retriever;
        $this->mapped_values_retriever = $mapped_values_retriever;
    }

    public static function build(): self
    {
        $freestyle_mapping_dao    = new FreestyleMappingDao();
        $semantic_status_provider = new \Cardwall_FieldProviders_SemanticStatusFieldRetriever();
        return new self(
            TrackerCollectionRetriever::build(),
            new MappedFieldRetriever(
                $semantic_status_provider,
                new FreestyleMappedFieldRetriever($freestyle_mapping_dao, \Tracker_FormElementFactory::instance())
            ),
            new MappedValuesRetriever(
                new FreestyleMappedFieldValuesRetriever($freestyle_mapping_dao, $freestyle_mapping_dao),
                $semantic_status_provider
            )
        );
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
        $value_mapping_presenters = [];
        $field_id                 = $this->mapped_field_retriever->getField($taskboard_tracker)
            ->mapOr(static fn($field) => $field->getId(), null);
        $mapped_values            = $this->mapped_values_retriever->getValuesMappedToColumn(
            $taskboard_tracker,
            $column
        );
        foreach ($mapped_values->getValueIds() as $value_id) {
            $value_mapping_presenters[] = new ListFieldValuePresenter((int) $value_id);
        }

        return new TrackerMappingPresenter($taskboard_tracker->getTrackerId(), $field_id, $value_mapping_presenters);
    }
}
