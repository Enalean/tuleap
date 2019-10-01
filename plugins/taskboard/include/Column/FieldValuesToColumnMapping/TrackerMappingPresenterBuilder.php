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
     * @var \Cardwall_OnTop_ConfigFactory
     */
    private $config_factory;
    /**
     * @var MappedFieldRetriever
     */
    private $mapped_field_retriever;

    public function __construct(
        \Cardwall_OnTop_ConfigFactory $config_factory,
        MappedFieldRetriever $mapped_field_retriever
    ) {
        $this->config_factory         = $config_factory;
        $this->mapped_field_retriever = $mapped_field_retriever;
    }

    /**
     * @return TrackerMappingPresenter[]
     */
    public function buildMappings(int $column_id, \Planning $planning): array
    {
        $config   = $this->config_factory->getOnTopConfigByPlanning($planning);
        $mappings = [];
        if ($config) {
            foreach ($config->getTrackers() as $tracker) {
                $mappings[] = $this->buildMappingForATracker($column_id, $config, $tracker);
            }
        }
        return $mappings;
    }

    private function buildMappingForATracker(
        int $column_id,
        \Cardwall_OnTop_Config $config,
        \Tracker $tracker
    ): TrackerMappingPresenter {
        $value_mapping_presenters = [];
        $mapping                  = $config->getMappingFor($tracker);
        $field                    = $this->mapped_field_retriever->getField($config, $tracker);

        if ($mapping !== null) {
            $value_mappings = $this->filterValueMappingsByColumn($column_id, $mapping->getValueMappings());
            foreach ($value_mappings as $value_mapping) {
                $value_mapping_presenters[] = new ListFieldValuePresenter((int) $value_mapping->getValueId());
            }
        }
        $field_id = $field !== null ? (int) $field->getId() : null;

        return new TrackerMappingPresenter((int) $tracker->getId(), $field_id, $value_mapping_presenters);
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
