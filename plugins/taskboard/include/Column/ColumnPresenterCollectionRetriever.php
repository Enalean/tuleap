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

use Cardwall_OnTop_ColumnDao;
use PFUser;
use Planning;
use Tuleap\Cardwall\Column\ColumnColorRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenterBuilder;

class ColumnPresenterCollectionRetriever
{
    /**
     * @var Cardwall_OnTop_ColumnDao
     */
    private $column_dao;
    /**
     * @var TrackerMappingPresenterBuilder
     */
    private $tracker_mapping_builder;

    public function __construct(
        Cardwall_OnTop_ColumnDao $column_dao,
        TrackerMappingPresenterBuilder $tracker_mapping_builder
    ) {
        $this->column_dao              = $column_dao;
        $this->tracker_mapping_builder = $tracker_mapping_builder;
    }

    /**
     * @return ColumnPresenter[]
     */
    public function getColumns(PFUser $user, \Planning_Milestone $milestone): array
    {
        $collection = [];
        $planning   = $milestone->getPlanning();
        foreach ($this->column_dao->searchColumnsByTrackerId($planning->getPlanningTrackerId()) as $row) {
            $column_id    = (int) $row['id'];
            $mappings     = $this->tracker_mapping_builder->buildMappings($column_id, $planning);
            $collection[] = new ColumnPresenter(
                $column_id,
                $row['label'],
                ColumnColorRetriever::getHeaderColorNameOrHex($row),
                $this->isCollapsed($user, $milestone, $column_id),
                $mappings
            );
        }

        return $collection;
    }

    private function isCollapsed(PFUser $user, \Planning_Milestone $milestone, int $column_id): bool
    {
        $preference_name = 'plugin_taskboard_collapse_column_' . $milestone->getArtifactId() . '_' . $column_id;

        return !empty($user->getPreference($preference_name));
    }
}
