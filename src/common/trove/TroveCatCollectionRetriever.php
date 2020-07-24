<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Trove;

use TroveCatDao;

class TroveCatCollectionRetriever
{

    /**
     * @var TroveCatDao
     */
    private $dao;

    public function __construct(TroveCatDao $dao)
    {
        $this->dao = $dao;
    }

    public function getCollection($project_id)
    {
        $categories = [];

        foreach ($this->dao->getProjectCategories($project_id) as $row) {
            $folders     = explode(" :: ", $row['fullpath']);
            $folders_ids = explode(" :: ", $row['fullpath_ids']);
            $folders_len = count($folders);
            $categories[] = new TroveCatPresenter(
                $folders_ids[$folders_len - 1],
                $folders[$folders_len - 1]
            );
        }

        return new TroveCatCollectionPresenter($categories);
    }
}
