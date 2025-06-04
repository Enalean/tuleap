<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Renderer;

use Tracker_Report_Renderer_Table_FunctionsAggregatesDao;

final readonly class AggregateRetriever
{
    public function __construct(private Tracker_Report_Renderer_Table_FunctionsAggregatesDao $aggregates_dao, private \Tracker_Report_Renderer_Table $renderer)
    {
    }

    public function retrieve(bool $use_data_from_db, array $columns): array
    {
        if ($use_data_from_db) {
            $aggregate_functions_raw = [$this->aggregates_dao->searchByRendererId($this->renderer->getId())];
        } else {
            $aggregate_functions_raw = $this->renderer->getAggregates();
        }
        $aggregates = [];
        foreach ($aggregate_functions_raw as $rows) {
            if ($rows) {
                foreach ($rows as $row) {
                    //is the field used as a column?
                    if (isset($columns[$row['field_id']])) {
                        if (! isset($aggregates[$row['field_id']])) {
                            $aggregates[$row['field_id']] = [];
                        }
                        $aggregates[$row['field_id']][] = $row['aggregate'];
                    }
                }
            }
        }

        return $aggregates;
    }
}
