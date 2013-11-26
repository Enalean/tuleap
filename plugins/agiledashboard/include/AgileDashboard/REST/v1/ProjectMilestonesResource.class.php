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

use \Tracker_ArtifactFactory;
use \Tracker_FormElementFactory;
use \TrackerFactory;
use \PlanningFactory;
use \Planning_MilestoneFactory;
use \PFUser;
use \Project;
use \Luracast\Restler\RestException;
use \Tuleap\REST\Header;

/**
 * Wrapper for milestone related REST methods
 */
class ProjectMilestonesResource {
    const MAX_LIMIT = 50;

    /**
     * Get the top milestones of a given project
     */
    public function get(PFUser $user, $project, $limit, $offset) {
        
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $all_milestones            = $this->getTopMilestones($user, $project);
        $milestones                = array_slice($all_milestones, $offset, $limit);
        $milestone_representations = array();

        foreach($milestones as $milestone) {
            $milestone_representations[] = new MilestoneRepresentation($milestone);
        }

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($all_milestones));

        return $milestone_representations;
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset) {
        $all_milestones = $this->getTopMilestones($user, $project);

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($all_milestones));
    }

    /**
     * Return all the top milestones of all the plannings of the project
     * @param PFUser $user
     * @param int $project_id
     * @return array Planning_ArtifactMilestone
     */
    private function getTopMilestones(PFUser $user, Project $project) {
        $milestone_factory = new Planning_MilestoneFactory(
            PlanningFactory::build(),
            Tracker_ArtifactFactory::instance(),
            Tracker_FormElementFactory::instance(),
            TrackerFactory::instance()
        );

        $top_milestones = array();
        $milestones     = $milestone_factory->getSubMilestones($user, $milestone_factory->getVirtualTopMilestone($user, $project));

        foreach ($milestones as $milestone) {
            $top_milestones[] = $milestone;
        }

        return $top_milestones;
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    private function sendAllowHeaders() {
        Header::allowOptionsGet();
    }
}
?>
