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
use Cardwall_OnTop_ConfigFactory;
use Planning_Milestone;
use Tracker;
use Tracker_FormElementFactory;
use TrackerFactory;

class TrackerMappingPresenterBuilder
{
    /** @var Cardwall_OnTop_ConfigFactory */
    private $config_factory;
    /** @var MappedFieldRetriever */
    private $mapped_field_retriever;
    /** @var MappedValuesRetriever */
    private $mapped_values_retriever;

    public function __construct(
        Cardwall_OnTop_ConfigFactory $config_factory,
        MappedFieldRetriever $mapped_field_retriever,
        MappedValuesRetriever $mapped_values_retriever
    ) {
        $this->config_factory          = $config_factory;
        $this->mapped_field_retriever  = $mapped_field_retriever;
        $this->mapped_values_retriever = $mapped_values_retriever;
    }

    public static function build(): self
    {
        return new self(
            new Cardwall_OnTop_ConfigFactory(
                TrackerFactory::instance(),
                Tracker_FormElementFactory::instance()
            ),
            MappedFieldRetriever::build(),
            MappedValuesRetriever::build()
        );
    }

    /**
     * @return TrackerMappingPresenter[]
     */
    public function buildMappings(Planning_Milestone $milestone, Cardwall_Column $column): array
    {
        $config   = $this->config_factory->getOnTopConfigByPlanning($milestone->getPlanning());
        $mappings = [];
        if ($config) {
            foreach ($config->getTrackers() as $tracker) {
                $mappings[] = $this->buildMappingForATracker(
                    $milestone->getArtifact()->getTracker(),
                    $tracker,
                    $column
                );
            }
        }
        return $mappings;
    }

    private function buildMappingForATracker(
        Tracker $milestone_tracker,
        Tracker $tracker,
        Cardwall_Column $column
    ): TrackerMappingPresenter {
        $value_mapping_presenters = [];
        $field                    = $this->mapped_field_retriever->getField($milestone_tracker, $tracker);
        $mapped_values            = $this->mapped_values_retriever->getValuesMappedToColumn(
            $milestone_tracker,
            $tracker,
            $column
        );
        foreach ($mapped_values->getValueIds() as $value_id) {
            $value_mapping_presenters[] = new ListFieldValuePresenter((int) $value_id);
        }
        $field_id = $field !== null ? (int) $field->getId() : null;

        return new TrackerMappingPresenter((int) $tracker->getId(), $field_id, $value_mapping_presenters);
    }
}
