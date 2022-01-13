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

    public function addColumnsFromRequest(\Codendi_Request $request, array $report_columns, \Docman_Report $report): void
    {
        $keep_ref_on_update_date = null;
        $is_there_a_sort         = false;

        foreach ($report_columns as $column_label) {
            $column = $this->column_factory->getColumnFromLabel($column_label);
            // set sort if provided by request parameters
            $column->initFromRequest($request);

            if ($column_label === 'update_date') {
                $keep_ref_on_update_date = $column;
            }
            if ($column->getSort() !== null) {
                $is_there_a_sort = true;
            }

            $report->addColumn($column);
        }

        $this->setSortOnLastUpdateDateWhenNoSortIsDefined($is_there_a_sort, $keep_ref_on_update_date);
    }

    /**
     * @param string[] $report_columns
     */
    public function addColumnsFromArray(array $report_columns, \Docman_Report $report): void
    {
        $keep_ref_on_update_date = null;
        $is_there_a_sort         = false;

        foreach ($report_columns as $column_label) {
            $column = $this->column_factory->getColumnFromLabel($column_label);

            if ($column_label === 'update_date') {
                $keep_ref_on_update_date = $column;
            }
            if ($column->getSort() !== null) {
                $is_there_a_sort = true;
            }

            $report->addColumn($column);
        }
        $this->setSortOnLastUpdateDateWhenNoSortIsDefined($is_there_a_sort, $keep_ref_on_update_date);
    }

    private function setSortOnLastUpdateDateWhenNoSortIsDefined(
        bool $is_there_a_sort,
        ?\Docman_ReportColumn $keep_ref_on_update_date,
    ): void {
        if (! $is_there_a_sort && $keep_ref_on_update_date !== null) {
            $keep_ref_on_update_date->setSort(PLUGIN_DOCMAN_SORT_DESC);
        }
    }
}
