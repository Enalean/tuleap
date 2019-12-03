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
    public function checkCollectionConsistency(CategoryCollection $submitted_categories): void
    {
        $top_categories_nb_max_values = [];
        foreach ($this->factory->getTopCategoriesWithNbMaxCategories() as $row) {
            $top_categories_nb_max_values[(int) $row['trove_cat_id']] = (int) $row['nb_max_values'];
        }

        $mandatory_categories = [];
        foreach ($this->factory->getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren() as $category) {
            $mandatory_categories[$category->getId()] = $category;
        }

        $reference_categories = $this->factory->getTree();

        foreach ($submitted_categories->getRootCategories() as $submitted_category) {
            if (! isset($top_categories_nb_max_values[$submitted_category->getId()])) {
                throw new NotRootCategoryException(sprintf('The category id %d is not a valid root category', $submitted_category->getId()));
            }

            if (count($submitted_category->getChildren()) > $top_categories_nb_max_values[$submitted_category->getId()]) {
                throw new NbMaxValuesException(sprintf('The category %d only allows %d values', $submitted_category->getId(), $top_categories_nb_max_values[$submitted_category->getId()]));
            }

            $this->findInCategoryTree($reference_categories, $submitted_category);

            if (isset($mandatory_categories[$submitted_category->getId()])) {
                unset($mandatory_categories[$submitted_category->getId()]);
            }
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
    }

    /**
     * @throws MissingMandatoryCategoriesException
     */
    public function update(Project $project, CategoryCollection $submitted_categories): void
    {
        $this->checkCollectionConsistency($submitted_categories);

        foreach ($submitted_categories->getRootCategories() as $category) {
            $this->doUpdate($project, $category);
        }
    }

    private function doUpdate(Project $project, TroveCat $root_category): void
    {
        $this->history_dao->groupAddHistory('changed_trove', '', $project->getID());

        $this->factory->removeProjectTopCategoryValue($project, $root_category->getId());
        foreach ($root_category->getChildren() as $selected_category) {
            $this->set_node_facade->setNode($project, $selected_category->getId(), $root_category->getId());
        }
    }

    /**
     * @param TroveCat[] $tree
     * @param TroveCat  $category
     */
    private function findInCategoryTree(array $tree, TroveCat $submitted_category)
    {
        foreach ($submitted_category->getChildren() as $selected_category) {
            $this->checkThatCategoryIdIsDifferentThanValueId($tree, $submitted_category, $selected_category);

            if (! $this->findTroveInTree($tree[$submitted_category->getId()], $selected_category)) {
                throw new InvalidValueForRootCategoryException(sprintf('%d does not belong to %s (%d) category hierarchy', $selected_category->getId(), $tree[$submitted_category->getId()]->getFullname(), $tree[$submitted_category->getId()]->getId()));
            }
        }
    }

    private function findTroveInTree(TroveCat $tree, TroveCat $category): bool
    {
        if ((int) $tree->getId() === (int) $category->getId()) {
            return true;
        }
        foreach ($tree->getChildren() as $children) {
            if ($this->findTroveInTree($children, $category)) {
                return true;
            }
        }
        return false;
    }

    private function checkThatCategoryIdIsDifferentThanValueId(
        array $tree,
        TroveCat $submitted_category,
        TroveCat $selected_category
    ): void {
        if ((int) $submitted_category->getId() === (int) $selected_category->getId()) {
            throw new InvalidValueForRootCategoryException(
                sprintf(
                    '%d does not belong to %s (%d) category hierarchy',
                    $selected_category->getId(),
                    $tree[$submitted_category->getId()]->getFullname(),
                    $tree[$submitted_category->getId()]->getId()
                )
            );
        }
    }
}
