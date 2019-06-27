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

namespace Test\Rest\Tracker;

use Guzzle\Http\Client;
use Test\Rest\RequestWrapper;

class TrackerFactory
{
    private $client;
    private $project_trackers;
    private $user_name;
    /**
 * @var RequestWrapper
*/
    private $rest_request;

    public function __construct(Client $client, RequestWrapper $rest_request, $project_id, $default_user_name)
    {
        $this->client       = $client;
        $this->rest_request = $rest_request;
        $this->user_name    = $default_user_name;
        $this->cacheProjectTrackers($project_id);
    }

    /**
     * @param string $tracker_name
     * @return Tracker
     */
    public function getTrackerRest($tracker_name)
    {
        return new Tracker(
            $this->client,
            $this->rest_request,
            $this->project_trackers[$tracker_name],
            $this->user_name
        );
    }

    private function cacheProjectTrackers($project_id)
    {
        $project_trackers = $this->getResponse($this->client->get("projects/$project_id/trackers"))->json();
        foreach ($project_trackers as $tracker) {
            $this->project_trackers[strtolower($tracker['item_name'])] = $tracker;
        }
    }

    private function getResponse($request)
    {
        return $this->rest_request->getResponseByName(
            $this->user_name,
            $request
        );
    }
}
