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
use TroveCatDao;

class ProjectCategoriesUpdater
{
    /**
     * @var TroveCatDao
     */
    private $dao;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var TroveSetNodeFacade
     */
    private $set_node_facade;

    public function __construct(TroveCatDao $dao, ProjectHistoryDao $history_dao, TroveSetNodeFacade $set_node_facade)
    {
        $this->dao             = $dao;
        $this->history_dao     = $history_dao;
        $this->set_node_facade = $set_node_facade;
    }

    /**
     * @param Project    $project
     * @param int[]      $submitted_categories
     */
    public function update(Project $project, array $submitted_categories): void
    {
        $top_categories_nb_max_values = [];
        foreach ($this->dao->getTopCategories() as $row) {
            $top_categories_nb_max_values[$row['trove_cat_id']] = $row['nb_max_values'];
        }

        $this->history_dao->groupAddHistory('changed_trove', "", $project->getID());
        foreach ($submitted_categories as $root_id => $trove_cat_ids) {
            if (! isset($top_categories_nb_max_values[$root_id])) {
                continue;
            }

            if (! is_array($trove_cat_ids)) {
                continue;
            }

            $this->dao->removeProjectTopCategoryValue($project->getID(), $root_id);

            $first_trove_cat_ids = \array_slice($trove_cat_ids, 1, $top_categories_nb_max_values[$root_id]);
            foreach ($first_trove_cat_ids as $submitted_category_id) {
                $this->set_node_facade->setNode($project, (int) $submitted_category_id, (int) $root_id);
            }
        }
    }
}
