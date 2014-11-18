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

namespace TuleapClient;

require_once 'Tracker.php';

class TrackerFactory {

    /** @var Request */
    private $request;
    private $project;
    private $project_trackers;

    public function __construct(Request $request, $project) {
        $this->request = $request;
        $this->project = $project;
        $this->cacheProjectTrackers($project);
    }

    /**
     * @param string $tracker_name
     * @return Test_Rest_Tracker
     */
    public function getTrackerRest($tracker_name) {
        return new Tracker(
            $this->request,
            $this->project_trackers[$tracker_name]
        );
    }

    private function cacheProjectTrackers(array $project) {
        $project_trackers = $this->request->getJson(
            $this->request->getClient()->get(
                $this->getTrackerResource($project)
            )
        );
        foreach ($project_trackers as $tracker) {
            $this->project_trackers[strtolower($tracker['item_name'])] = $tracker;
        }
    }

    private function getTrackerResource(array $project) {
        foreach ($project['resources'] as $resource) {
            if ($resource['type'] == 'trackers') {
                return $resource['uri'];
            }
        }
    }
}