<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\Search;

use Docman_ReportColumnFactory;

final class ColumnReportAugmenter
{
    public function __construct(private Docman_ReportColumnFactory $column_factory)
    {
    }

    /**
     * @param string[] $columnsOnReport
     */
    public function addColumnsFromRequest(\Codendi_Request $request, array $columnsOnReport, \Docman_Report $report): void
    {
        $keepRefOnUpdateDate = null;
        $thereIsAsort        = false;

        foreach ($columnsOnReport as $colLabel) {
            $column = $this->column_factory->getColumnFromLabel($colLabel);
            $column->initFromRequest($request);

            // If no sort, sort on update_date in DESC by default
            if ($colLabel === 'update_date') {
                $keepRefOnUpdateDate = $column;
            }
            if ($column->getSort() !== null) {
                $thereIsAsort = true;
            }

            $report->addColumn($column);
        }
        if (! $thereIsAsort && $keepRefOnUpdateDate !== null) {
            $keepRefOnUpdateDate->setSort(PLUGIN_DOCMAN_SORT_DESC);
        }
    }
}
