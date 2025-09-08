<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TrackerFunctions\Administration;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\RetrieveTracker;

final class ActiveTrackerRetrieverMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly RetrieveTracker $retrieve_tracker)
    {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tracker = $this->retrieve_tracker->getTrackerById($request->getAttribute('id'));
        if (! $tracker) {
            throw new NotFoundException();
        }

        if ($tracker->isDeleted()) {
            throw new NotFoundException();
        }

        $enriched_request = $request->withAttribute(\Tuleap\Tracker\Tracker::class, $tracker);

        return $handler->handle($enriched_request);
    }
}
