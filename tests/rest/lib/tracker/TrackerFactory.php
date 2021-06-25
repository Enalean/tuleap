<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Test\Rest\RequestWrapper;

class TrackerFactory
{
    private RequestFactoryInterface $request_factory;
    private StreamFactoryInterface $stream_factory;
    private $project_trackers;
    private $user_name;
    /**
 * @var RequestWrapper
*/
    private $rest_request;

    public function __construct(RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, RequestWrapper $rest_request, $project_id, $default_user_name)
    {
        $this->request_factory = $request_factory;
        $this->stream_factory  = $stream_factory;
        $this->rest_request    = $rest_request;
        $this->user_name       = $default_user_name;
        $this->cacheProjectTrackers($project_id);
    }

    /**
     * @param string $tracker_name
     * @return Tracker
     */
    public function getTrackerRest($tracker_name)
    {
        return new Tracker(
            $this->request_factory,
            $this->stream_factory,
            $this->rest_request,
            $this->project_trackers[$tracker_name],
            $this->user_name
        );
    }

    private function cacheProjectTrackers($project_id)
    {
        $response         = $this->getResponse($this->request_factory->createRequest('GET', "projects/$project_id/trackers"));
        $project_trackers = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        foreach ($project_trackers as $tracker) {
            $this->project_trackers[strtolower($tracker['item_name'])] = $tracker;
        }
    }

    private function getResponse(RequestInterface $request): ResponseInterface
    {
        return $this->rest_request->getResponseByName(
            $this->user_name,
            $request
        );
    }
}
