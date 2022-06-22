<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree\Search;

use Tuleap\Docman\REST\v1\Search\SearchColumn;
use Tuleap\Docman\REST\v1\Search\SearchColumnCollectionBuilder;
use Tuleap\Document\Config\Project\IRetrieveColumns;

final class ListOfSearchColumnDefinitionPresenterBuilder
{
    public function __construct(
        private SearchColumnCollectionBuilder $column_collection_builder,
        private IRetrieveColumns $columns_dao,
    ) {
    }

    /**
     * @return SearchColumnDefinitionPresenter[]
     */
    public function getColumns(\Project $project, \Docman_MetadataFactory $metadata_factory): array
    {
        return array_map(
            static fn(SearchColumn $column): SearchColumnDefinitionPresenter => new SearchColumnDefinitionPresenter(
                $column->getName(),
                $column->getLabel(),
                $column->isMultipleValueAllowed()
            ),
            $this->getFilteredColumns($project, $metadata_factory)
        );
    }

    /**
     * @return SearchColumn[]
     */
    private function getFilteredColumns(\Project $project, \Docman_MetadataFactory $metadata_factory): array
    {
        $columns = $this->column_collection_builder->getCollection($metadata_factory)->getColumns();

        $columns_to_display = $this->columns_dao->searchByProjectId((int) $project->getID());
        if (empty($columns_to_display)) {
            return $columns;
        }

        array_push($columns_to_display, \Docman_MetadataFactory::HARDCODED_METADATA_TITLE_LABEL, \Docman_MetadataFactory::HARDCODED_METADATA_ID_LABEL);

        return array_values(
            array_filter(
                $columns,
                static fn(SearchColumn $column) => in_array($column->getName(), $columns_to_display, true),
            )
        );
    }
}
