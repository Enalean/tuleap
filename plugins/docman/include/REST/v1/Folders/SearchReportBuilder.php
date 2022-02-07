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

namespace Tuleap\Docman\REST\v1\Folders;

use Docman_FilterGlobalText;
use Docman_Report;
use Tuleap\Docman\REST\v1\Search\PostSearchRepresentation;
use Tuleap\Docman\Search\AlwaysThereColumnRetriever;
use Tuleap\Docman\Search\ColumnReportAugmenter;

class SearchReportBuilder
{
    public function __construct(
        private \Docman_FilterFactory $filter_factory,
        private AlwaysThereColumnRetriever $always_there_column_retriever,
        private ColumnReportAugmenter $column_report_builder,
    ) {
    }

    public function buildReport(\Docman_Folder $item, PostSearchRepresentation $search): Docman_Report
    {
        $report = new Docman_Report();
        $report->initFromRow(
            [
                'group_id' => $item->getGroupId(),
                'item_id'  => $item->getId(),
            ]
        );

        $global_search_metadata = $this->filter_factory->getGlobalSearchMetadata();
        $filter                 = new Docman_FilterGlobalText($global_search_metadata, []);
        $filter->setValue($search->global_search);
        $report->addFilter($filter);

        if ($search->type) {
            $type_filter = new \Docman_FilterItemType($this->filter_factory->getItemTypeSearchMetadata());

            $human_readable_value_to_internal_value = [
                'file'     => PLUGIN_DOCMAN_ITEM_TYPE_FILE,
                'wiki'     => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
                'embedded' => PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
                'empty'    => PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
                'link'     => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                'folder'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            ];
            $type_filter->setValue($human_readable_value_to_internal_value[$search->type]);
            $report->addFilter($type_filter);
        }

        $columns = $this->always_there_column_retriever->getColumns();
        $this->column_report_builder->addColumnsFromArray($columns, $report);


        return $report;
    }
}
