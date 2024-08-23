<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column;

use Cardwall_Column;
use Cardwall_OnTop_ColumnDao;
use Cardwall_OnTop_Config_ColumnFactory;
use PFUser;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappingDao;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenterBuilder;
use Tuleap\Taskboard\Tracker\TrackerCollectionRetriever;

class ColumnPresenterCollectionRetriever
{
    /** @var Cardwall_OnTop_Config_ColumnFactory */
    private $column_factory;
    /** @var TrackerMappingPresenterBuilder */
    private $tracker_mapping_builder;

    public function __construct(
        Cardwall_OnTop_Config_ColumnFactory $column_factory,
        TrackerMappingPresenterBuilder $tracker_mapping_builder,
    ) {
        $this->column_factory          = $column_factory;
        $this->tracker_mapping_builder = $tracker_mapping_builder;
    }

    public static function build(): self
    {
        $freestyle_mapping_dao    = new FreestyleMappingDao();
        $semantic_status_provider = new \Cardwall_FieldProviders_SemanticStatusFieldRetriever();
        return new self(
            new Cardwall_OnTop_Config_ColumnFactory(new Cardwall_OnTop_ColumnDao()),
            new TrackerMappingPresenterBuilder(
                TrackerCollectionRetriever::build(),
                new MappedFieldRetriever(
                    $semantic_status_provider,
                    new FreestyleMappedFieldRetriever($freestyle_mapping_dao, \Tracker_FormElementFactory::instance())
                ),
                new MappedValuesRetriever(
                    new FreestyleMappedFieldValuesRetriever($freestyle_mapping_dao, $freestyle_mapping_dao),
                    $semantic_status_provider
                )
            )
        );
    }

    /**
     * @return ColumnPresenter[]
     */
    public function getColumns(PFUser $user, \Planning_Milestone $milestone): array
    {
        $collection = [];
        $planning   = $milestone->getPlanning();
        $columns    = $this->column_factory->getDashboardColumns($planning->getPlanningTracker());
        foreach ($columns as $column) {
            \assert($column instanceof Cardwall_Column);
            $mappings     = $this->tracker_mapping_builder->buildMappings($milestone, $column);
            $collection[] = new ColumnPresenter(
                $column,
                $this->isCollapsed($user, $milestone, $column),
                $mappings
            );
        }
        return $collection;
    }

    private function isCollapsed(PFUser $user, \Planning_Milestone $milestone, Cardwall_Column $column): bool
    {
        $preference_name = 'plugin_taskboard_collapse_column_' . $milestone->getArtifactId() . '_' . $column->getId();

        return ! empty($user->getPreference($preference_name));
    }
}
