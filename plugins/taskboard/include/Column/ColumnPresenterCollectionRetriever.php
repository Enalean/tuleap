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
    public function getColumns(\Planning $planning): array
    {
        $collection = [];
        foreach ($this->column_dao->searchColumnsByTrackerId($planning->getPlanningTrackerId()) as $row) {
            $column_id    = (int) $row['id'];
            $mappings     = $this->tracker_mapping_builder->buildMappings($column_id, $planning);
            $collection[] = new ColumnPresenter(
                $column_id,
                $row['label'],
                $this->getColor($row),
                $mappings
            );
        }

        return $collection;
    }

    private function getColor(array $row): string
    {
        if ($row['tlp_color_name']) {
            return $row['tlp_color_name'];
        }

        $r = $row['bg_red'];
        $g = $row['bg_green'];
        $b = $row['bg_blue'];
        if ($r !== null && $g !== null && $b !== null) {
            return \ColorHelper::RGBToHexa($r, $g, $b);
        }

        return '';
    }
}
