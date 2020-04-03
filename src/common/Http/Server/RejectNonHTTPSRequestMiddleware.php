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

namespace Tuleap\Http\Server;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RejectNonHTTPSRequestMiddleware implements MiddlewareInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;

    public function __construct(ResponseFactoryInterface $response_factory, StreamFactoryInterface $stream_factory)
    {
        $this->response_factory = $response_factory;
        $this->stream_factory   = $stream_factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getScheme() !== 'https') {
            return $this->response_factory->createResponse(400)
                ->withHeader('Content-Type', 'text/plain')
                ->withBody($this->stream_factory->createStream('HTTPS is mandatory'));
        }
        return $handler->handle($request);
    }
}
