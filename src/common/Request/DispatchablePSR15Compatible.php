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

namespace Tuleap\Request;

use GuzzleHttp\Psr7\ServerRequest;
use HTTPRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Http\Server\MiddlewareDispatcher;
use Tuleap\Http\Server\RequestHandlerAsMiddleware;
use Tuleap\Layout\BaseLayout;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use function array_merge;

abstract class DispatchablePSR15Compatible implements DispatchableWithRequest, RequestHandlerInterface
{
    /**
     * @var EmitterInterface
     */
    private $emitter;
    /**
     * @var MiddlewareDispatcher
     */
    private $middleware_dispatcher;

    public function __construct(EmitterInterface $emitter, MiddlewareInterface ...$middleware_stack)
    {
        $this->emitter               = $emitter;
        $this->middleware_dispatcher = new MiddlewareDispatcher(
            ...array_merge($middleware_stack, [new RequestHandlerAsMiddleware($this)])
        );
    }

    final public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $server_request = ServerRequest::fromGlobals();
        foreach ($variables as $variable_name => $variable_value) {
            $server_request = $server_request->withAttribute($variable_name, $variable_value);
        }
        $server_request = $server_request->withAttribute(BaseLayout::class, $layout);

        $has_response_been_emitted = $this->emitter->emit($this->middleware_dispatcher->handle($server_request));

        if (! $has_response_been_emitted) {
            throw new PSR15PipelineResponseEmissionException();
        }
    }
}
