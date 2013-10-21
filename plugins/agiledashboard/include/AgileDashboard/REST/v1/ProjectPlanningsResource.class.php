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
use \Luracast\Restler\RestException;

/**
 * Wrapper for milestone related REST methods
 */
class ProjectPlanningsResource {
    const MAX_LIMIT = 50;

    public function get(PFUser $user, Project $project, $limit, $offset) {

        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $planning_representations = array();
        $project_id               = $project->getId();

        $all_plannings = PlanningFactory::build()->getPlannings($user, $project_id);
        $plannings     = array_slice($all_plannings, $offset, $limit);

        foreach($plannings as $planning) {
            $planning_representations[] = new PlanningRepresentation($planning);
        }

        $this->sendPaginationHeaders($limit, $offset, count($all_plannings));

        return $planning_representations;
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset) {
        $all_plannings = PlanningFactory::build()->getPlannings($user, $project->getID());

        header('Allow: GET, OPTIONS');
        $this->sendPaginationHeaders($limit, $offset, count($all_plannings));
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        header('X-PAGINATION-LIMIT: '. $limit);
        header('X-PAGINATION-OFFSET: '. $offset);
        header('X-PAGINATION-SIZE: '. $size);
        header('X-PAGINATION-LIMIT-MAX: '. self::MAX_LIMIT);
    }
}
?>
