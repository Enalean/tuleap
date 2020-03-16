<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
namespace Tuleap\AgileDashboard\REST\v1;

use PlanningFactory;
use PFUser;
use Project;
use Luracast\Restler\RestException;
use Tuleap\REST\Header;

/**
 * Wrapper for milestone related REST methods
 */
class ProjectPlanningsResource
{
    public const MAX_LIMIT = 50;

    public function get(PFUser $user, Project $project, $limit, $offset)
    {
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $planning_representations = array();
        $project_id               = $project->getId();

        $all_plannings = PlanningFactory::build()->getPlannings($user, $project_id);
        $plannings     = array_slice($all_plannings, $offset, $limit);

        foreach ($plannings as $planning) {
            $planning_representation = new PlanningRepresentation();
            $planning_representation->build($planning);
            $planning_representations[] = $planning_representation;
        }

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($all_plannings));

        return $planning_representations;
    }

    private function limitValueIsAcceptable($limit)
    {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset)
    {
        $this->sendAllowHeaders();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGet();
    }
}
