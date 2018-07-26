<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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
use Tuleap\REST\JsonDecoder;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

/**
 * Wrapper for tracker related REST methods
 */
class ProjectTrackersResource
{
    const MAX_LIMIT              = 50;
    const MINIMAL_REPRESENTATION = 'minimal';

    /**
     * Get all the tracker representation of a given project
     *
     * @throws RestException
     */
    public function get(PFUser $user, Project $project, $representation, $query, $limit, $offset)
    {
        if (! $this->limitValueIsAcceptable($limit)) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $builder                 = new Tracker_REST_TrackerRestBuilder(Tracker_FormElementFactory::instance());
        $all_trackers            = TrackerFactory::instance()->getTrackersByGroupIdUserCanView($project->getId(), $user);
        $trackers                = array_slice($all_trackers, $offset, $limit);
        $tracker_representations = array();

        $filter_on_tracker_administration_permission = $this->mustFilterOnTrackerAdministration($query);

        foreach($trackers as $tracker) {
            if ($filter_on_tracker_administration_permission && ! $tracker->userIsAdmin($user)) {
                continue;
            }

            if ($representation === self::MINIMAL_REPRESENTATION) {
                $tracker_minimal_representation = new MinimalTrackerRepresentation();
                $tracker_minimal_representation->build($tracker);
                $tracker_representations[] = $tracker_minimal_representation;
            } else {
                $tracker_representations[] = $builder->getTrackerRepresentation($user, $tracker);
            }
        }

        $this->sendAllowHeaders();
        $this->sendPaginationHeaders($limit, $offset, count($all_trackers));

        return $tracker_representations;
    }

    /**
     * @return bool
     * @throws RestException
     */
    private function mustFilterOnTrackerAdministration($query)
    {
        if ($query === '') {
            return false;
        }

        $json_decoder = new JsonDecoder();

        if ($query && ! $json_decoder->looksLikeJson($query)) {
            throw new RestException(400, 'Query must be in Json');
        }

        $json_query = $json_decoder->decodeAsAnArray('query', $query);
        if (! isset($json_query['is_tracker_admin'])) {
            throw new RestException(400, 'You can only filter on "is_tracker_admin"');
        }

        if (isset($json_query['is_tracker_admin']) && ! $json_query['is_tracker_admin']) {
            throw new RestException(
                400,
                "Filtering for trackers you are not administrator is not supported. Use 'is_tracker_admin': true"
            );
        }

        return true;
    }

    private function limitValueIsAcceptable($limit) {
        return $limit <= self::MAX_LIMIT;
    }

    public function options(PFUser $user, Project $project, $limit, $offset) {
        $this->sendAllowHeaders();
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
