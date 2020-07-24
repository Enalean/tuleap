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

namespace Tuleap\Label\Widget;

use Project;
use Tuleap\Project\Label\LabelDao;

class ProjectLabelRetriever
{
    /**
     * @var LabelDao
     */
    private $dao;

    public function __construct(LabelDao $dao)
    {
        $this->dao = $dao;
    }

    public function getLabelsByProject(Project $project)
    {
        $labels = [];
        foreach ($this->dao->searchLabelsUsedByProject($project->getID()) as $label) {
            $labels[] = $label;
        }

        return $labels;
    }
}
