<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use TroveCat;
use TroveCatFactory;

class CategoryCollectionConsistencyChecker
{
    private TroveCatFactory $trove_cat_factory;

    public function __construct(TroveCatFactory $trove_cat_factory)
    {
        $this->trove_cat_factory = $trove_cat_factory;
    }

    /**
     * @throws NotRootCategoryException
     * @throws NbMaxValuesException
     * @throws MissingMandatoryCategoriesException
     */
    public function checkCollectionConsistency(CategoryCollection $submitted_categories): void
    {
        $top_categories_nb_max_values = [];
        foreach ($this->trove_cat_factory->getTopCategoriesWithNbMaxCategories() as $row) {
            $top_categories_nb_max_values[(int) $row['trove_cat_id']] = (int) $row['nb_max_values'];
        }

        $mandatory_categories = [];
        foreach ($this->trove_cat_factory->getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren() as $category) {
            $mandatory_categories[$category->getId()] = $category;
        }

        $reference_categories = $this->trove_cat_factory->getTree();

        foreach ($submitted_categories->getRootCategories() as $submitted_category) {
            if (! isset($top_categories_nb_max_values[$submitted_category->getId()])) {
                throw new NotRootCategoryException($submitted_category->getId());
            }

            if (count($submitted_category->getChildren()) > $top_categories_nb_max_values[$submitted_category->getId()]) {
                throw new NbMaxValuesException(
                    $submitted_category->getId(),
                    $top_categories_nb_max_values[$submitted_category->getId()]
                );
            }

            $this->findInCategoryTree($reference_categories, $submitted_category);

            if (isset($mandatory_categories[$submitted_category->getId()])) {
                unset($mandatory_categories[$submitted_category->getId()]);
            }
        }

        if (count($mandatory_categories) !== 0) {
            throw new MissingMandatoryCategoriesException(
                implode(
                    ', ',
                    array_map(
                        static function (TroveCat $category) {
                            return sprintf('%s (%d)', $category->getFullname(), $category->getId());
                        },
                        array_values($mandatory_categories)
                    )
                )
            );
        }
    }

    /**
     * @param TroveCat[] $tree
     */
    private function findInCategoryTree(array $tree, TroveCat $submitted_category): void
    {
        foreach ($submitted_category->getChildren() as $selected_category) {
            $this->checkThatCategoryIdIsDifferentThanValueId($tree, $submitted_category, $selected_category);

            if (! $this->findTroveInTree($tree[$submitted_category->getId()], $selected_category)) {
                throw new InvalidValueForRootCategoryException(
                    $selected_category->getId(),
                    $tree[$submitted_category->getId()]->getFullname(),
                    (int) $tree[$submitted_category->getId()]->getId()
                );
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
        TroveCat $selected_category,
    ): void {
        if ((int) $submitted_category->getId() === (int) $selected_category->getId()) {
            throw new InvalidValueForRootCategoryException(
                $selected_category->getId(),
                $tree[$submitted_category->getId()]->getFullname(),
                (int) $tree[$submitted_category->getId()]->getId()
            );
        }
    }
}
