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

use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Document\Tree\ListOfSearchCriterionPresenterBuilder;
use Tuleap\Document\Tree\SearchCriterionListPresenter;
use Tuleap\Document\Tree\SearchCriterionPresenter;

final class SearchCriteriaFilter
{
    public function __construct(
        private ListOfSearchCriterionPresenterBuilder $criteria_builder,
        private IRetrieveCriteria $criteria_dao,
    ) {
    }

    public function getCriteria(\Project $project, \Docman_MetadataFactory $metadata_factory): array
    {
        $selectable_criteria = $this->criteria_builder->getAllCriteria(
            $metadata_factory,
            new ItemStatusMapper(new \Docman_SettingsBo($project->getID())),
            $project
        );

        $selected_criteria_names = $this->criteria_dao->searchByProjectId((int) $project->getID());

        return array_values(
            array_map(
                static fn(SearchCriterionPresenter|SearchCriterionListPresenter $criterion) => [
                    'name' => $criterion->name,
                    'label' => $criterion->label,
                    'is_selected' => empty($selected_criteria_names) || in_array($criterion->name, $selected_criteria_names, true),
                ],
                $selectable_criteria,
            ),
        );
    }
}
