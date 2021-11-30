<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Categories;

use CSRFSynchronizerToken;
use Project;

class UpdateCategoriesProcessor
{
    private CategoryCollectionConsistencyChecker $category_collection_consistency_checker;
    private ProjectCategoriesUpdater $updater;

    public function __construct(
        CategoryCollectionConsistencyChecker $category_collection_consistency_checker,
        ProjectCategoriesUpdater $updater,
    ) {
        $this->category_collection_consistency_checker = $category_collection_consistency_checker;
        $this->updater                                 = $updater;
    }

    /**
     * @throws NotRootCategoryException
     * @throws NbMaxValuesException
     * @throws MissingMandatoryCategoriesException
     */
    public function processUpdate(Project $project, CSRFSynchronizerToken $csrf, CategoryCollection $submitted_categories): void
    {
        $csrf->check();

        $this->category_collection_consistency_checker->checkCollectionConsistency($submitted_categories);
        $this->updater->update($project, $submitted_categories);
    }
}
