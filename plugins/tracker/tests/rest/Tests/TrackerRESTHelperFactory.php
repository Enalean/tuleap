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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Tests;

use Psl\Json;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\REST\RequestWrapper;

final class TrackerRESTHelperFactory
{
    /** @var array<string, array> */
    private array $project_trackers;

    public function __construct(
        private readonly RequestWrapper $rest_request,
        private readonly RequestFactoryInterface $request_factory,
        private readonly StreamFactoryInterface $stream_factory,
        int $project_id,
        private string $rest_user_name,
    ) {
        $this->cacheProjectTrackers($project_id);
    }

    public function getTrackerRest(string $tracker_name): TrackerRESTHelper
    {
        return new TrackerRESTHelper(
            $this->request_factory,
            $this->stream_factory,
            $this->rest_request,
            $this->project_trackers[$tracker_name],
            $this->rest_user_name
        );
    }

    private function cacheProjectTrackers(int $project_id): void
    {
        $response         = $this->getResponse($this->request_factory->createRequest('GET', "projects/$project_id/trackers"));
        $project_trackers = Json\decode($response->getBody()->getContents());
        foreach ($project_trackers as $tracker) {
            $this->project_trackers[strtolower($tracker['item_name'])] = $tracker;
        }
    }

    private function getResponse(RequestInterface $request): ResponseInterface
    {
        return $this->rest_request->getResponseByName($this->rest_user_name, $request);
    }
}
