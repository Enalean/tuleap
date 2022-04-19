<?php
/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Document\Config\Project;

use Tuleap\Docman\REST\v1\Search\SearchColumn;
use Tuleap\Docman\REST\v1\Search\SearchColumnCollectionBuilder;

final class SearchColumnFilter
{
    public function __construct(
        private SearchColumnCollectionBuilder $column_builder,
        private IRetrieveColumns $columns_dao,
    ) {
    }

    public function getColumns(\Project $project, \Docman_MetadataFactory $metadata_factory): array
    {
        $all_columns = $this->column_builder->getCollection($metadata_factory)->getColumns();

        $selectable_columns = array_filter(
            $all_columns,
            static fn(SearchColumn $column) => $column->getName() !== \Docman_MetadataFactory::HARDCODED_METADATA_TITLE_LABEL
        );

        $selected_columns_names = $this->columns_dao->searchByProjectId((int) $project->getID());

        return array_values(
            array_map(
                static fn(SearchColumn $column) => [
                    'name' => $column->getName(),
                    'label' => $column->getLabel(),
                    'is_selected' => empty($selected_columns_names) || in_array($column->getName(), $selected_columns_names, true),
                ],
                $selectable_columns,
            ),
        );
    }
}
