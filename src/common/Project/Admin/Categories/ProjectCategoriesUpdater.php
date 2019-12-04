<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\Categories;

use Project;
use ProjectHistoryDao;
use TroveCat;
use TroveCatFactory;

class ProjectCategoriesUpdater
{
    /**
     * @var TroveCatFactory
     */
    private $factory;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var TroveSetNodeFacade
     */
    private $set_node_facade;

    public function __construct(TroveCatFactory $factory, ProjectHistoryDao $history_dao, TroveSetNodeFacade $set_node_facade)
    {
        $this->factory     = $factory;
        $this->history_dao = $history_dao;
        $this->set_node_facade = $set_node_facade;
    }

    /**
     * @throws MissingMandatoryCategoriesException
     */
    public function update(Project $project, CategoryCollection $submitted_categories): void
    {
        $top_categories_nb_max_values = [];
        foreach ($this->factory->getTopCategories() as $row) {
            $top_categories_nb_max_values[(int) $row['trove_cat_id']] = (int) $row['nb_max_values'];
        }

        $mandatory_categories = [];
        foreach ($this->factory->getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren() as $category) {
            $mandatory_categories[$category->getId()] = $category;
        }

        $categories_to_update = [];
        foreach ($submitted_categories->getRootCategories() as $root_category) {
            if (! isset($top_categories_nb_max_values[$root_category->getId()])) {
                continue;
            }

            if (isset($mandatory_categories[$root_category->getId()])) {
                unset($mandatory_categories[$root_category->getId()]);
            }

            $categories_to_update[] = $root_category;
        }

        if (count($mandatory_categories) !== 0) {
            throw new MissingMandatoryCategoriesException(
                sprintf(
                    'Mandatory categories where missing: %s',
                    implode(
                        ', ',
                        array_map(
                            static function (TroveCat $category) {
                                return sprintf('%s (%d)', $category->getFullname(), $category->getId());
                            },
                            array_values($mandatory_categories)
                        )
                    )
                )
            );
        }

        foreach ($categories_to_update as $category) {
            $this->doUpdate($project, $category, $top_categories_nb_max_values[$category->getId()]);
        }
    }

    private function doUpdate(Project $project, TroveCat $root_category, int $nb_max_values): void
    {
        $this->history_dao->groupAddHistory('changed_trove', '', $project->getID());

        $trove_cat_ids = $root_category->getChildren();

        $this->factory->removeProjectTopCategoryValue($project, $root_category->getId());

        for ($i = 0; $i < $nb_max_values; $i++) {
            if (! isset($trove_cat_ids[$i])) {
                break;
            }
            $this->set_node_facade->setNode($project, $trove_cat_ids[$i]->getId(), $root_category->getId());
        }
    }
}
