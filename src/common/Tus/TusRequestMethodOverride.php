<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tus;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TusRequestMethodOverride implements MiddlewareInterface
{
    private const HEADER_NAME         = 'X-Http-Method-Override';
    private const OVERRIDABLE_METHODS = ['PATCH', 'POST', 'PUT', 'DELETE'];

    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;

    public function __construct(ResponseFactoryInterface $response_factory)
    {
        $this->response_factory = $response_factory;
    }


    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $override_method = $request->getHeaderLine(self::HEADER_NAME);
        if ($override_method === '') {
            return $handler->handle($request);
        }

        if (! in_array($request->getMethod(), self::OVERRIDABLE_METHODS, true)) {
            return $this->response_factory->createResponse(405);
        }

        if (! in_array($override_method, self::OVERRIDABLE_METHODS, true)) {
            return $this->response_factory->createResponse(405);
        }

        $request = $request->withMethod($override_method);

        return $handler->handle($request);
    }
}
