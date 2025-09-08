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

namespace Tuleap\Http\Server;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewareDispatcher implements RequestHandlerInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    public function __construct(MiddlewareInterface ...$middleware_stack)
    {
        if (count($middleware_stack) === 0) {
            throw new EmptyMiddlewareStackException();
        }
        $this->middlewares = $middleware_stack;
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = current($this->middlewares);
        if ($middleware === false) {
            throw new MissingMiddlewareResponseException();
        }
        next($this->middlewares);
        return $middleware->process($request, $this);
    }
}
