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
namespace Tuleap\Tracker\REST\v1;

use \TrackerFactory;
use \Tracker_REST_TrackerRestBuilder;
use \Tracker_FormElementFactory;
use \PFUser;
use \Project;
use \Luracast\Restler\RestException;
use \Tuleap\REST\Header;

/**
 * Wrapper for tracker related REST methods
 */
class ProjectTrackersResource {
    const MAX_LIMIT = 50;

    /**
     * Get all the tracker reprensations of a given project
     */
    public function get(PFUser $user, Project $project, $limit, $offset) {
        if (! $this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $builder                 = new Tracker_REST_TrackerRestBuilder(Tracker_FormElementFactory::instance());
        $all_trackers            = TrackerFactory::instance()->getTrackersByGroupIdUserCanView($project->getId(), $user);
        $trackers                = array_slice($all_trackers, $offset, $limit);
        $tracker_representations = array();

        foreach($trackers as $tracker) {

            $tracker_representations[] = $builder->getTrackerRepresentation($user, $tracker);
        }

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($all_trackers));

        return $tracker_representations;
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset) {
        $all_trackers = TrackerFactory::instance()->getTrackersByGroupIdUserCanView($project->getId(), $user);

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($all_trackers));
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        header('X-PAGINATION-LIMIT: '. $limit);
        header('X-PAGINATION-OFFSET: '. $offset);
        header('X-PAGINATION-SIZE: '. $size);
        header('X-PAGINATION-LIMIT-MAX: '. self::MAX_LIMIT);
    }

    private function sendAllowHeaders() {
        Header::allowOptionsGet();
    }
}
?>
