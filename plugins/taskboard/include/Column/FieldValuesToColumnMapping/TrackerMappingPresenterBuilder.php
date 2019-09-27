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

class TrackerMappingPresenterBuilder
{
    /**
     * @var \Cardwall_OnTop_Dao
     */
    private $cardwall_dao;
    /**
     * @var \Cardwall_OnTop_Config_ColumnFactory
     */
    private $column_factory;
    /**
     * @var \Cardwall_OnTop_Config_TrackerMappingFactory
     */
    private $tracker_mapping_factory;

    public function __construct(
        \Cardwall_OnTop_Dao $cardwall_dao,
        \Cardwall_OnTop_Config_ColumnFactory $column_factory,
        \Cardwall_OnTop_Config_TrackerMappingFactory $tracker_mapping_factory
    ) {
        $this->cardwall_dao            = $cardwall_dao;
        $this->column_factory          = $column_factory;
        $this->tracker_mapping_factory = $tracker_mapping_factory;
    }

    /**
     * @return TrackerMappingPresenter[]
     */
    public function buildMappings(int $column_id, \Planning $planning): array
    {
        $config   = new \Cardwall_OnTop_Config(
            $planning->getPlanningTracker(),
            $this->cardwall_dao,
            $this->column_factory,
            $this->tracker_mapping_factory
        );
        $mappings = [];
        foreach ($config->getTrackers() as $tracker) {
            $mappings[] = $this->buildMappingForATracker($column_id, $config, $tracker);
        }
        return $mappings;
    }

    private function buildMappingForATracker(
        int $column_id,
        \Cardwall_OnTop_Config $config,
        \Tracker $tracker
    ): TrackerMappingPresenter {
        $mapping                  = $config->getMappingFor($tracker);
        $value_mapping_presenters = [];

        if ($mapping !== null) {
            $value_mappings = $this->filterValueMappingsByColumn($column_id, $mapping->getValueMappings());
            foreach ($value_mappings as $value_mapping) {
                $value_mapping_presenters[] = new ListFieldValuePresenter((int) $value_mapping->getValueId());
            }
        }

        return new TrackerMappingPresenter((int) $tracker->getId(), $value_mapping_presenters);
    }

    /**
     * @param \Cardwall_OnTop_Config_ValueMapping[] $value_mappings
     * @return \Cardwall_OnTop_Config_ValueMapping[]
     */
    private function filterValueMappingsByColumn(int $column_id, array $value_mappings): array
    {
        $filtered = [];
        foreach ($value_mappings as $value_mapping) {
            if ((int) $value_mapping->getColumnId() === $column_id) {
                $filtered[] = $value_mapping;
            }
        }
        return $filtered;
    }
}
