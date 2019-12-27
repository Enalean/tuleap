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
 */

namespace Tuleap\Project\Label;

use Project;
use Tuleap\Label\PaginatedCollectionsOfLabelsBuilder;

class LabelsCurlyCoatedRetriever
{
    /**
     * @var LabelDao
     */
    private $dao;
    /**
     * @var PaginatedCollectionsOfLabelsBuilder
     */
    private $builder;

    public function __construct(PaginatedCollectionsOfLabelsBuilder $builder, LabelDao $dao)
    {
        $this->dao     = $dao;
        $this->builder = $builder;
    }

    public function getPaginatedMatchingLabelsForProject(Project $project, $keyword, $limit, $offset)
    {
        $result     = $this->dao->searchLabelsLikeKeywordByProjectId($project->getId(), $keyword, $limit, $offset);
        $total_size = $this->dao->foundRows();

        return $this->builder->build($result, $total_size);
    }
}
