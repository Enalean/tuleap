<?php
/**
  * Copyright (c) Enalean, 2014. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

namespace Tuleap\News;

use Project;

class NewsItemForWidgetDataMapper
{
    /**
     * @var NewsDao $dao
     */
    private $dao;

    public function __construct(NewsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return NewsItem[]
     */
    public function fetchAll(Project $project)
    {
        $rows = $this->dao->fetchAll($project->getID());

        $items = array();
        foreach ($rows as $row) {
            $items[] = new NewsItem($row);
        }

        return $items;
    }

    public function updatePromotedItems(Project $project, $promoted_ids)
    {
        $this->dao->updatePromotedItems((array) $promoted_ids, $project->getID());
    }
}
