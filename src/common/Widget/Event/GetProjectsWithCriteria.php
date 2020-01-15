<?php
/**
 *  Copyright Enalean (c) 2018. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Widget\Event;

use Project;
use Tuleap\Project\PaginatedProjects;

class GetProjectsWithCriteria
{
    public const NAME = "getProjectsWithCriteria";

    /**
     * @var array
     */
    private $query;
    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var Project[]
     */
    private $projects_with_criteria = [];

    /**
     * @param     $query
     * @param int $limit
     * @param int $offset
     */
    public function __construct(array $query, $limit, $offset)
    {
        $this->query                  = $query;
        $this->limit                  = $limit;
        $this->offset                 = $offset;
        $this->projects_with_criteria = [];
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return PaginatedProjects
     */
    public function getProjectsWithCriteria()
    {
        return new PaginatedProjects($this->projects_with_criteria, count($this->projects_with_criteria));
    }

    /**
     * @param Project[] $projects
     */
    public function addProjectsWithCriteria(array $projects)
    {
        $this->projects_with_criteria = array_merge($this->projects_with_criteria, $projects);
    }
}
