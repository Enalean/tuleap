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

use \PlanningFactory;
use \Luracast\Restler\RestException;
use \Planning;
use \Tuleap\REST\Header;

class PlanningResource {

    const MAX_LIMIT = 100;

    private $milestone_factory;


    public function __construct() {
        $this->milestone_factory = new \Planning_MilestoneFactory(
            PlanningFactory::build(),
            \Tracker_ArtifactFactory::instance(),
            \Tracker_FormElementFactory::instance(),
            \TrackerFactory::instance()
        );
    }

    /**
     *
     * Get the milestones identified by planning id
     *
     * @url GET {id}/milestones
     *
     * @param string $id ID of the planning
     * @param string $limit The number of element displayed per page
     * @param string $offset The id of the first element to display
     *
     * @return array of milestones
     *
     * @throws 403
     * @throws 404
     */
    protected function getMilestones($id, $limit = 10, $offset = 0) {
        if (! $this->limitValueIsAcceptable($limit)) {
             throw new RestException(406, 'Maximum value for limit exceeded');
        }

        return $this->getMilestonesByPlanning($this->getPlanning($id), $limit, $offset);
    }

    /**
     * @url OPTIONS
     */
    protected function options() {
        $this->sendAllowHeaders();
    }

    /**
     * @url OPTIONS {id}/milestones
     */
    protected function optionsForMilestones($id) {
        $this->getPlanning($id);
        $this->sendAllowHeadersForMilestones();
    }

    /**
     * @param integer $id
     *
     * @return Planning
     */
    private function getPlanning($id) {
        try {
            $planning = \PlanningFactory::build()->getPlanning($id);
        } catch (\Project_AccessProjectNotFoundException $exception) {
            throw new RestException(404);
        } catch (\Project_AccessException $exception) {
            throw new RestException(403, $exception->getMessage());
        }
        if (! $planning) {
            throw new RestException(404, 'Planning not found');
        }

        return $planning;
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    private function getCurrentUser() {
        return \UserManager::instance()->getCurrentUser();
    }

    private function getMilestonesByPlanning(Planning $planning, $limit, $offset) {
        $all_milestones = array();
        $milestones = $this->milestone_factory->getAllBareMilestones($this->getCurrentUser(), $planning);
        foreach ($milestones as $milestone) {
            $all_milestones[] = new MilestoneInfoRepresentation($milestone);
        }
        $milestones_representations = array_slice($all_milestones, $offset, $limit);
        $this->sendAllowHeadersForMilestones();
        $this->sendPaginationHeaders($limit, $offset, count($milestones_representations));
        return $milestones_representations;
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        header('X-PAGINATION-LIMIT: '. $limit);
        header('X-PAGINATION-OFFSET: '. $offset);
        header('X-PAGINATION-SIZE: '. $size);
        header('X-PAGINATION-LIMIT-MAX: '. self::MAX_LIMIT);
    }

    private function sendAllowHeaders() {
        Header::allowOptions();
    }

    private function sendAllowHeadersForMilestones() {
        Header::allowOptionsGet();
    }
}

?>
