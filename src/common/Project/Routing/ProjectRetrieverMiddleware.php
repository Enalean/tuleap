<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Project\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

final class ProjectRetrieverMiddleware implements MiddlewareInterface
{
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;

    public function __construct(ProjectRetriever $project_retriever)
    {
        $this->project_retriever = $project_retriever;
    }

    /**
     * @throws NotFoundException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $enriched_request = $request->withAttribute(
            \Project::class,
            $this->project_retriever->getProjectFromId($request->getAttribute('project_id'))
        );
        return $handler->handle($enriched_request);
    }
}
